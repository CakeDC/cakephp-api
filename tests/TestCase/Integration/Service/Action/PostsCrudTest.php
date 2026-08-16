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
use CakeDC\Api\Test\ConfigTrait;
use CakeDC\Api\Test\Settings;
use CakeDC\Api\TestSuite\IntegrationTestCase;

/**
 * Real HTTP CRUD integration tests for the generic posts service.
 */
class PostsCrudTest extends IntegrationTestCase
{
    use ConfigTrait;

    protected array $fixtures = [
        'plugin.CakeDC/Api.Users',
        'plugin.CakeDC/Api.Posts',
        'plugin.CakeDC/Api.Tags',
    ];

    /**
     * setUp
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        Configure::write('App.fullBaseUrl', 'http://example.com');
        $this->_tokenAccess();
        $this->_loadDefaultExtensions([]);
        $this->getDefaultUser(Settings::USER1);
    }

    /**
     * tearDown
     *
     * @return void
     */
    protected function tearDown(): void
    {
        parent::tearDown();
        Configure::write('Test.Api.Extension');
    }

    public function testIndex(): void
    {
        $this->sendRequest('/posts', 'GET');
        $result = $this->getJsonResponse();
        $this->assertSuccess($result);
        $this->assertCount(4, $result['data']);
        $this->assertSame([1, 2, 3, 4], \Cake\Utility\Hash::extract($result['data'], '{n}.id'));
    }

    public function testView(): void
    {
        $this->sendRequest('/posts/1', 'GET');
        $result = $this->getJsonResponse();
        $this->assertSuccess($result);
        $expected = [
            'id' => 1,
            'title' => 'First Post',
            'body' => 'First Post Body',
            'published' => 'Y',
        ];
        $this->assertEquals($expected, $result['data']);
    }

    public function testViewNotFound(): void
    {
        $this->sendRequest('/posts/999', 'GET');
        $result = $this->getJsonResponse();
        $this->assertError($result, 404);
        $this->assertNull($result['data']);
    }

    public function testAdd(): void
    {
        $post = [
            'title' => 'New Post',
            'body' => 'New Post Body',
            'published' => 'Y',
        ];
        $this->sendRequest('/posts', 'POST', $post);
        $result = $this->getJsonResponse();
        $this->assertSuccess($result);
        $this->assertArrayHasKey('id', $result['data']);
        $id = $result['data']['id'];

        $this->sendRequest('/posts/' . $id, 'GET');
        $result = $this->getJsonResponse();
        $this->assertSuccess($result);

        $res = array_intersect_key($post, $result['data']);
        $this->assertEquals($post, $res);
    }

    public function testAddValidationError(): void
    {
        $this->sendRequest('/posts', 'POST', ['body' => 'missing title']);
        $result = $this->getJsonResponse();
        $this->assertError($result, 422);
    }

    public function testEdit(): void
    {
        $post = [
            'title' => 'Updated Post',
            'body' => 'Updated Post Body',
            'published' => 'Y',
        ];
        $this->sendRequest('/posts/1', 'PUT', $post);
        $result = $this->getJsonResponse();
        $this->assertSuccess($result);
        $this->assertEquals(1, $result['data']['id']);

        $this->sendRequest('/posts/1', 'GET');
        $result = $this->getJsonResponse();
        $this->assertSuccess($result);
        $res = array_intersect_key($post, $result['data']);
        $this->assertEquals($post, $res);
    }

    public function testDelete(): void
    {
        $this->sendRequest('/posts/1', 'DELETE');
        $result = $this->getJsonResponse();
        $this->assertSuccess($result);

        $this->sendRequest('/posts/1', 'GET');
        $result = $this->getJsonResponse();
        $this->assertError($result, 404);
    }
}
