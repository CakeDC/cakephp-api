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
 * Nested resource integration tests: /posts/{id}/tags.
 *
 * Exercises the FallbackService HasMany auto-nesting plus the Nested
 * extension parent-scoping (a tag only shows under its own post).
 */
class PostsNestedTagsTest extends IntegrationTestCase
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
        $this->sendRequest('/posts/1/tags', 'GET');
        $result = $this->getJsonResponse();
        $this->assertSuccess($result);
        $this->assertEquals([1, 2], Hash::extract($result['data'], '{n}.id'));

        $this->sendRequest('/posts/2/tags', 'GET');
        $result = $this->getJsonResponse();
        $this->assertSuccess($result);
        $this->assertEquals([3, 4], Hash::extract($result['data'], '{n}.id'));
    }

    public function testViewScopedToParent(): void
    {
        $this->sendRequest('/posts/1/tags/1', 'GET');
        $result = $this->getJsonResponse();
        $this->assertSuccess($result);
        $this->assertEquals(1, $result['data']['id']);

        // tag 3 belongs to post 2 — must not be visible under post 1
        $this->sendRequest('/posts/1/tags/3', 'GET');
        $result = $this->getJsonResponse();
        $this->assertError($result, 404);
    }

    public function testAddScopedToParent(): void
    {
        $this->sendRequest('/posts/1/tags', 'POST', ['name' => 'tag-new']);
        $result = $this->getJsonResponse();
        $this->assertSuccess($result);
        $this->assertArrayHasKey('id', $result['data']);
        $newId = $result['data']['id'];

        // new tag must be owned by post 1
        $this->sendRequest('/posts/1/tags', 'GET');
        $result = $this->getJsonResponse();
        $this->assertSuccess($result);
        $ids = Hash::extract($result['data'], '{n}.id');
        $this->assertContains($newId, $ids);
    }

    public function testDeleteScopedToParent(): void
    {
        $this->sendRequest('/posts/1/tags/1', 'DELETE');
        $result = $this->getJsonResponse();
        $this->assertSuccess($result);

        // deleting post-2 tag under post-1 must be rejected
        $this->sendRequest('/posts/1/tags/3', 'DELETE');
        $result = $this->getJsonResponse();
        $this->assertError($result, 404);
    }
}
