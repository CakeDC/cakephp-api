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
 * Custom route path aliases: GET /posts/featured and GET /posts/category/{category}.
 */
class PostsRouteAliasTest extends IntegrationTestCase
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

    public function testFeaturedAlias(): void
    {
        $this->sendRequest('/posts/featured', 'GET');
        $result = $this->getJsonResponse();
        $this->assertSuccess($result);
        $this->assertEquals([1, 2, 3, 4], Hash::extract($result['data'], '{n}.id'));
    }

    public function testCategoryAliasWithParam(): void
    {
        $this->sendRequest('/posts/category/tech', 'GET');
        $result = $this->getJsonResponse();
        $this->assertSuccess($result);
        $this->assertCount(4, $result['data']);
    }

    public function testMethodMismatchOnAlias(): void
    {
        // 'featured' is mapped as GET only — POST must not match
        $this->sendRequest('/posts/featured', 'POST', ['title' => 'nope']);
        $result = $this->getJsonResponse();
        $this->assertError($result);
    }
}
