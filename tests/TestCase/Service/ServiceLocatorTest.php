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
use CakeDC\Api\Service\FallbackService;
use CakeDC\Api\Service\Locator\ServiceLocator;
use CakeDC\Api\Service\Service;
use CakeDC\Api\Service\ServiceRegistry;
use CakeDC\Api\Test\ConfigTrait;
use CakeDC\Api\TestSuite\TestCase;
use RuntimeException;

/**
 * Unit tests for the service locator registry behaviour.
 */
class ServiceLocatorTest extends TestCase
{
    use ConfigTrait;

    protected function setUp(): void
    {
        parent::setUp();
        Configure::write('Api.ServiceFallback', \CakeDC\Api\Service\FallbackService::class);
        Configure::write('Api.useVersioning', false);
        Configure::write('Api.versionPrefix', 'v');
        Configure::delete('Api.defaultVersion');
    }

    protected function tearDown(): void
    {
        Configure::delete('Api.ServiceFallback');
        Configure::delete('Api.useVersioning');
        Configure::delete('Api.versionPrefix');
        Configure::delete('Api.defaultVersion');
        ServiceRegistry::getServiceLocator()->clear();
        parent::tearDown();
    }

    protected function buildOptions(string $service = 'articles', string $baseUrl = '/articles', ?string $version = null): array
    {
        $this->_initializeRequest([
            'params' => ['service' => $service],
        ], 'GET');
        $serviceName = $this->request->getParam('service');

        return [
            'version' => $version,
            'service' => $serviceName,
            'request' => $this->request,
            'response' => $this->response,
            'baseUrl' => $baseUrl,
        ];
    }

    public function testVersioningDisabledResolvesBaseService(): void
    {
        $locator = new ServiceLocator();
        $service = $locator->get('articles', $this->buildOptions());
        $this->assertInstanceOf(\CakeDC\Api\Test\App\Service\ArticlesService::class, $service);
    }

    public function testVersioningResolvesVersionedService(): void
    {
        Configure::write('Api.useVersioning', true);
        $locator = new ServiceLocator();
        $service = $locator->get('articles', $this->buildOptions(version: 'v1'));
        $this->assertInstanceOf(\CakeDC\Api\Test\App\Service\v1\ArticlesService::class, $service);
        $this->assertSame('articles', $service->getName());
    }

    public function testVersioningUsesDefaultVersion(): void
    {
        Configure::write('Api.useVersioning', true);
        Configure::write('Api.defaultVersion', '1');
        $locator = new ServiceLocator();
        $service = $locator->get('articles', $this->buildOptions());
        $this->assertInstanceOf(\CakeDC\Api\Test\App\Service\v1\ArticlesService::class, $service);
    }

    public function testVersioningUnresolvedVersionFallsBack(): void
    {
        // version 'ver1' maps to Service/ver1/ namespace which has no ArticlesService
        Configure::write('Api.useVersioning', true);
        $locator = new ServiceLocator();
        $service = $locator->get('articles', $this->buildOptions(version: 'ver1'));
        $this->assertInstanceOf(\CakeDC\Api\Service\FallbackService::class, $service);
    }

    public function testGetResolvesExistingService(): void
    {
        $locator = new ServiceLocator();
        $service = $locator->get('articles', $this->buildOptions());
        $this->assertInstanceOf(Service::class, $service);
        $this->assertSame('articles', $service->getName());
    }

    public function testGetCachesInstance(): void
    {
        $locator = new ServiceLocator();
        $options = $this->buildOptions();
        $first = $locator->get('articles', $options);
        $second = $locator->get('articles', $options);
        $this->assertSame($first, $second);
    }

    public function testGetWithDifferentOptionsThrows(): void
    {
        $this->expectException(RuntimeException::class);
        $locator = new ServiceLocator();
        $locator->get('articles', $this->buildOptions('articles', '/articles'));
        $locator->get('articles', $this->buildOptions('articles', '/different'));
    }

    public function testGetWithRefreshCreatesNewInstance(): void
    {
        $locator = new ServiceLocator();
        $options = $this->buildOptions();
        $first = $locator->get('articles', $options);
        $second = $locator->get('articles', $options + ['refresh' => true]);
        $this->assertNotSame($first, $second);
    }

    public function testGetUnknownServiceFallsBack(): void
    {
        $locator = new ServiceLocator();
        $options = $this->buildOptions('unknown', '/unknown');
        $service = $locator->get('unknown', $options);
        $this->assertInstanceOf(FallbackService::class, $service);
        $this->assertSame('unknown', $service->getName());
    }

    public function testSetConfigAliasOptions(): void
    {
        $locator = new ServiceLocator();
        $locator->setConfig('custom', ['className' => \CakeDC\Api\Test\App\Service\ArticlesService::class]);
        $this->assertSame(['className' => \CakeDC\Api\Test\App\Service\ArticlesService::class], $locator->getConfig('custom'));
    }

    public function testSetConfigArrayOverwrites(): void
    {
        $locator = new ServiceLocator();
        $locator->setConfig([
            'a' => ['foo' => 'bar'],
            'b' => ['baz' => 1],
        ]);
        $config = $locator->getConfig();
        $this->assertSame(['foo' => 'bar'], $config['a']);
        $this->assertSame(['baz' => 1], $config['b']);
    }

    public function testSetConfigOnExistingInstanceThrows(): void
    {
        $this->expectException(RuntimeException::class);
        $locator = new ServiceLocator();
        $locator->get('articles', $this->buildOptions());
        $locator->setConfig('Articles', ['className' => 'x']);
    }

    public function testExists(): void
    {
        $locator = new ServiceLocator();
        $this->assertFalse($locator->exists('Articles'));
        $locator->get('articles', $this->buildOptions());
        $this->assertTrue($locator->exists('Articles'));
    }

    public function testSet(): void
    {
        $locator = new ServiceLocator();
        $options = $this->buildOptions();
        $service = new FallbackService($options);
        $locator->set('Articles', $service);
        $this->assertSame($service, $locator->get('articles'));
    }

    public function testRemove(): void
    {
        $locator = new ServiceLocator();
        $options = $this->buildOptions();
        $locator->get('articles', $options);
        $locator->remove('Articles');
        $this->assertFalse($locator->exists('Articles'));
        $fresh = $locator->get('articles', $options);
        $this->assertInstanceOf(Service::class, $fresh);
    }

    public function testClear(): void
    {
        $locator = new ServiceLocator();
        $locator->get('articles', $this->buildOptions());
        $locator->clear();
        $this->assertFalse($locator->exists('Articles'));
        $this->assertSame([], $locator->getConfig());
    }
}
