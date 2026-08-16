<?php
declare(strict_types=1);

/**
 * Copyright 2016 - 2026, Cake Development Corporation (http://cakedc.com)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright 2016 - 2026, Cake Development Corporation (http://cakedc.com)
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

namespace CakeDC\Api\Test\TestCase\Service;

use Cake\Core\Configure;
use CakeDC\Api\Service\ConfigReader;
use CakeDC\Api\TestSuite\TestCase;

/**
 * Unit tests for the service/action options config reader.
 */
class ConfigReaderTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Configure::write('Api.Service', []);
        Configure::write('Api.useVersioning', false);
    }

    protected function tearDown(): void
    {
        Configure::delete('Api.Service');
        Configure::delete('Api.useVersioning');
        parent::tearDown();
    }

    protected function writeService(array $config): void
    {
        Configure::write('Api.Service', $config);
    }

    public function testServiceOptionsDefaultsOnly(): void
    {
        $this->writeService([
            'default' => ['options' => ['version' => null, 'adapter' => 'Db']],
        ]);
        $reader = new ConfigReader();
        $options = $reader->serviceOptions('articles');
        $this->assertSame('Db', $options['adapter']);
    }

    public function testServiceOptionsServiceOverridesDefaults(): void
    {
        $this->writeService([
            'default' => ['options' => ['version' => null, 'adapter' => 'Db', 'limit' => 10]],
            'articles' => ['options' => ['limit' => 25]],
        ]);
        $reader = new ConfigReader();
        $options = $reader->serviceOptions('articles');
        $this->assertSame('Db', $options['adapter']);
        $this->assertSame(25, $options['limit']);
    }

    public function testServiceOptionsNoConfig(): void
    {
        $reader = new ConfigReader();
        $this->assertSame([], $reader->serviceOptions('articles'));
    }

    public function testServiceOptionsVersioned(): void
    {
        Configure::write('Api.useVersioning', true);
        $this->writeService([
            'default' => ['options' => ['adapter' => 'Db', 'limit' => 10]],
            'v1' => [
                'default' => ['options' => ['limit' => 50, 'foo' => 'bar']],
                'articles' => ['options' => ['limit' => 100]],
            ],
        ]);
        $reader = new ConfigReader();
        $options = $reader->serviceOptions('articles', 'v1');
        $this->assertSame('Db', $options['adapter']);
        $this->assertSame(100, $options['limit']);
        $this->assertSame('bar', $options['foo']);
    }

    public function testServiceOptionsVersionedNullDoesNotOverride(): void
    {
        Configure::write('Api.useVersioning', true);
        $this->writeService([
            'v1' => [
                'default' => ['options' => ['limit' => 50]],
                'articles' => ['options' => ['limit' => null]],
            ],
        ]);
        $reader = new ConfigReader();
        $options = $reader->serviceOptions('articles', 'v1');
        $this->assertSame(50, $options['limit']);
    }

    public function testActionOptionsServiceLevel(): void
    {
        $this->writeService([
            'articles' => [
                'Action' => [
                    'default' => ['auth' => true],
                    'Index' => ['limit' => 5],
                ],
            ],
        ]);
        $reader = new ConfigReader();
        $options = $reader->actionOptions('articles', 'index');
        $this->assertTrue($options['auth']);
        $this->assertSame(5, $options['limit']);
    }

    public function testActionOptionsDefaultByNamePrecedence(): void
    {
        // NOTE: Hash::merge gives precedence to the second (defaults) argument, so the
        // service-level Action.default wins over default.Action.<Name>. Locked-in behavior.
        $this->writeService([
            'default' => [
                'Action' => [
                    'default' => ['auth' => true, 'limit' => 10],
                    'Index' => ['limit' => 99, 'custom' => true],
                ],
            ],
            'articles' => [
                'Action' => [
                    'default' => ['limit' => 5],
                ],
            ],
        ]);
        $reader = new ConfigReader();
        $options = $reader->actionOptions('articles', 'index');
        $this->assertSame(5, $options['limit']);
        $this->assertTrue($options['auth']);
        $this->assertTrue($options['custom']);
    }

    public function testActionOptionsNoConfig(): void
    {
        $reader = new ConfigReader();
        $this->assertSame([], $reader->actionOptions('articles', 'index'));
    }

    public function testActionOptionsVersioned(): void
    {
        Configure::write('Api.useVersioning', true);
        $this->writeService([
            'v1' => [
                'default' => ['Action' => ['default' => ['auth' => false]]],
                'articles' => [
                    'Action' => [
                        'default' => ['limit' => 20],
                        'Index' => ['limit' => 5],
                    ],
                ],
            ],
        ]);
        $reader = new ConfigReader();
        $options = $reader->actionOptions('articles', 'index', 'v1');
        $this->assertSame(20, $options['limit']);
        $this->assertFalse($options['auth']);
    }

    public function testActionNameIsCamelized(): void
    {
        $this->writeService([
            'articles' => [
                'Action' => [
                    'default' => ['auth' => true],
                    'Index' => ['limit' => 5],
                ],
            ],
        ]);
        $reader = new ConfigReader();
        $options = $reader->actionOptions('articles', 'index');
        $this->assertSame(5, $options['limit']);
    }
}
