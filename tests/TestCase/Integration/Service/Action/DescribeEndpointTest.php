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

namespace CakeDC\Api\Test\TestCase\Integration\Service\Action;

use Cake\Core\Configure;
use Cake\Utility\Hash;
use CakeDC\Api\Test\ConfigTrait;
use CakeDC\Api\Test\Settings;
use CakeDC\Api\TestSuite\IntegrationTestCase;

/**
 * Real HTTP checks for the /describe endpoint (schema, validators, relations, actions).
 */
class DescribeEndpointTest extends IntegrationTestCase
{
    use ConfigTrait;

    protected array $fixtures = [
        'plugin.CakeDC/Api.Articles',
        'plugin.CakeDC/Api.Authors',
        'plugin.CakeDC/Api.Tags',
        'plugin.CakeDC/Api.ArticlesTags',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        Configure::write('App.fullBaseUrl', 'http://example.com');
        $this->_tokenAccess();
        $this->getDefaultUser(Settings::USER1);
    }

    public function testDescribeArticles(): void
    {
        $this->sendRequest('/describe', 'GET', ['service' => 'articles']);
        $result = $this->getJsonResponse();
        $this->assertSuccess($result);
        $data = $result['data'];
        $this->assertArrayHasKey('schema', $data);
        $this->assertArrayHasKey('columns', $data['schema']);
        $this->assertArrayHasKey('title', $data['schema']['columns']);
        $this->assertArrayHasKey('validators', $data);
        $this->assertArrayHasKey('relations', $data);
        $this->assertArrayHasKey('BelongsTo', $data['relations']);
        $this->assertArrayHasKey('actions', $data);
        $this->assertSame('GET', Hash::get($data, 'actions.index.method'));
        $this->assertSame('POST', Hash::get($data, 'actions.add.method'));
        $this->assertSame('PUT', Hash::get($data, 'actions.edit.method'));
        $this->assertSame('DELETE', Hash::get($data, 'actions.delete.method'));
        $this->assertStringStartsWith('http://example.com/api', (string)Hash::get($data, 'actions.index.href'));
    }

    public function testDescribeRequiresServiceParam(): void
    {
        $this->sendRequest('/describe', 'GET', []);
        $result = $this->getJsonResponse();
        $this->assertError($result, 422);
        $this->assertErrorMessage($result, 'Validation failed');
    }

    public function testDescribeUnknownService(): void
    {
        $this->sendRequest('/describe', 'GET', ['service' => 'nonexistent']);
        $result = $this->getJsonResponse();
        $this->assertTrue(is_array($result));
    }
}
