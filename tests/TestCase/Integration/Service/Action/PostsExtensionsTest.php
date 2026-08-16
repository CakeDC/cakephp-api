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
 * Simple real HTTP integration checks for the Paginate / Sort / Filter
 * extensions on the posts resource, plus their combination and nesting.
 */
class PostsExtensionsTest extends IntegrationTestCase
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
        $this->_loadDefaultExtensions('CakeDC/Api.Paginate');
        $this->_loadDefaultExtensions('CakeDC/Api.Sort');
        $this->_loadDefaultExtensions('CakeDC/Api.Filter');
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

    public function testPaginatePages(): void
    {
        $this->sendRequest('/posts', 'GET', ['limit' => 2, 'page' => 1]);
        $result = $this->getJsonResponse();
        $this->assertSuccess($result);
        $this->assertEquals([1, 2], Hash::extract($result['data'], '{n}.id'));
        $this->assertEquals(2, $result['pagination']['pages']);

        $this->sendRequest('/posts', 'GET', ['limit' => 2, 'page' => 2]);
        $result = $this->getJsonResponse();
        $this->assertSuccess($result);
        $this->assertEquals([3, 4], Hash::extract($result['data'], '{n}.id'));
    }

    public function testPaginateOutOfRangePage(): void
    {
        $this->sendRequest('/posts', 'GET', ['limit' => 2, 'page' => 9]);
        $result = $this->getJsonResponse();
        $this->assertSuccess($result);
        $this->assertSame([], $result['data']);
    }

    public function testPaginateLimitAboveCount(): void
    {
        $this->sendRequest('/posts', 'GET', ['limit' => 10]);
        $result = $this->getJsonResponse();
        $this->assertSuccess($result);
        $this->assertEquals([1, 2, 3, 4], Hash::extract($result['data'], '{n}.id'));
    }

    public function testSortByTitle(): void
    {
        $this->sendRequest('/posts', 'GET', ['sort' => 'title']);
        $result = $this->getJsonResponse();
        $this->assertSuccess($result);
        $this->assertEquals([1, 4, 2, 3], Hash::extract($result['data'], '{n}.id'));
    }

    public function testSortByTitleDesc(): void
    {
        $this->sendRequest('/posts', 'GET', ['sort' => 'title', 'direction' => 'desc']);
        $result = $this->getJsonResponse();
        $this->assertSuccess($result);
        $this->assertEquals([3, 2, 4, 1], Hash::extract($result['data'], '{n}.id'));
    }

    public function testFilterEquality(): void
    {
        $this->sendRequest('/posts', 'GET', ['title' => 'First Post']);
        $result = $this->getJsonResponse();
        $this->assertSuccess($result);
        $this->assertEquals([1], Hash::extract($result['data'], '{n}.id'));
    }

    public function testFilterLike(): void
    {
        $this->sendRequest('/posts', 'GET', ['title$like' => 'Post']);
        $result = $this->getJsonResponse();
        $this->assertSuccess($result);
        $this->assertEquals([1, 2, 3, 4], Hash::extract($result['data'], '{n}.id'));
    }

    public function testFilterComparisonOperators(): void
    {
        $this->sendRequest('/posts', 'GET', ['id$gt' => '1']);
        $result = $this->getJsonResponse();
        $this->assertSuccess($result);
        $this->assertEquals([2, 3, 4], Hash::extract($result['data'], '{n}.id'));

        $this->sendRequest('/posts', 'GET', ['id$ge' => '2', 'id$le' => '3']);
        $result = $this->getJsonResponse();
        $this->assertSuccess($result);
        $this->assertEquals([2, 3], Hash::extract($result['data'], '{n}.id'));

        $this->sendRequest('/posts', 'GET', ['id$ne' => '1']);
        $result = $this->getJsonResponse();
        $this->assertSuccess($result);
        $this->assertEquals([2, 3, 4], Hash::extract($result['data'], '{n}.id'));
    }

    public function testCombinedSortPaginateFilter(): void
    {
        $this->sendRequest('/posts', 'GET', ['title$like' => 'Post', 'sort' => 'id', 'direction' => 'desc', 'limit' => 2]);
        $result = $this->getJsonResponse();
        $this->assertSuccess($result);
        $this->assertEquals([4, 3], Hash::extract($result['data'], '{n}.id'));
        $this->assertEquals(4, $result['pagination']['count']);
    }

    public function testNestedWithPagination(): void
    {
        $this->sendRequest('/posts/1/tags', 'GET', ['limit' => 1]);
        $result = $this->getJsonResponse();
        $this->assertSuccess($result);
        $this->assertCount(1, $result['data']);
        $this->assertEquals(2, $result['pagination']['count']);
    }

    public function testNestedWithSort(): void
    {
        $this->sendRequest('/posts/1/tags', 'GET', ['sort' => 'name', 'direction' => 'desc']);
        $result = $this->getJsonResponse();
        $this->assertSuccess($result);
        $this->assertEquals(['tag2', 'tag1'], Hash::extract($result['data'], '{n}.name'));
    }
}
