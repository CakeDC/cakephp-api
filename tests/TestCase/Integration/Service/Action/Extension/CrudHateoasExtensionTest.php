<?php
/**
 * Copyright 2016 - 2019, Cake Development Corporation (http://cakedc.com)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright 2016 - 2019, Cake Development Corporation (http://cakedc.com)
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

namespace CakeDC\Api\Test\TestCase\Integration\Service\Action\Extension;

use CakeDC\Api\TestSuite\IntegrationTestCase;
use CakeDC\Api\Test\ConfigTrait;
use CakeDC\Api\Test\FixturesTrait;
use CakeDC\Api\Test\Settings;
use Cake\Core\Configure;
use Cake\Utility\Hash;

/**
 * Class CrudHateoasExtensionTest
 *
 * @package CakeDC\Api\Test\TestCase\Integration\Service\Extension
 */
class CrudHateoasExtensionTest extends IntegrationTestCase
{

    use ConfigTrait;
    use FixturesTrait;

    /**
     * setUp
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        Configure::write('App.fullBaseUrl', 'http://example.com');
        $this->_tokenAccess();
        $this->_loadDefaultExtensions('CakeDC/Api.CrudHateoas');
        $this->_loadDefaultExtensions('CakeDC/Api.Paginate');
        $this->getDefaultUser(Settings::USER1);
    }

    /**
     * tearDown
     *
     * @return void
     */
    public function tearDown(): void
    {
        parent::tearDown();
        Configure::write('Test.Api.Extension', null);
    }

    public function testView()
    {
        $this->sendRequest('/articles/1', 'GET', []);
        $result = $this->getJsonResponse();
        $this->assertSuccess($result);
        $links = [
            [
                'name' => 'self',
                'href' => 'http://example.com/api/articles/1',
                'rel' => '/api/articles/1',
                'method' => 'GET'
            ],
            [
                'name' => 'articles:edit',
                'href' => 'http://example.com/api/articles/1',
                'rel' => '/api/articles/1',
                'method' => 'PUT'
            ],
            [
                'name' => 'articles:delete',
                'href' => 'http://example.com/api/articles/1',
                'rel' => '/api/articles/1',
                'method' => 'DELETE'
            ],
            [
                'name' => 'articles:index',
                'href' => 'http://example.com/api/articles',
                'rel' => '/api/articles',
                'method' => 'GET'
            ]
        ];
        $this->assertEquals($links, Hash::get($result, 'links'));
    }

    public function testIndex()
    {
        $this->sendRequest('/articles', 'GET', ['limit' => 4]);
        $result = $this->getJsonResponse();
        $this->assertSuccess($result);
        $links = [
            [
                'name' => 'self',
                'href' => 'http://example.com/api/articles',
                'rel' => '/api/articles',
                'method' => 'GET'
            ],
            [
                'name' => 'articles:add',
                'href' => 'http://example.com/api/articles',
                'rel' => '/api/articles',
                'method' => 'POST'
            ],
        ];
        $this->assertEquals($links, Hash::get($result, 'links'));
    }

    public function testViewNested()
    {
        $this->sendRequest('/authors/1/articles/1', 'GET', []);
        $result = $this->getJsonResponse();
        $this->assertSuccess($result);
        $links = [
            [
                'name' => 'self',
                'href' => 'http://example.com/api/authors/1/articles/1',
                'rel' => '/api/authors/1/articles/1',
                'method' => 'GET'
            ],
            [
                'name' => 'articles:edit',
                'href' => 'http://example.com/api/authors/1/articles/1',
                'rel' => '/api/authors/1/articles/1',
                'method' => 'PUT'
            ],
            [
                'name' => 'articles:delete',
                'href' => 'http://example.com/api/authors/1/articles/1',
                'rel' => '/api/authors/1/articles/1',
                'method' => 'DELETE'
            ],
            [
                'name' => 'articles:index',
                'href' => 'http://example.com/api/authors/1/articles',
                'rel' => '/api/authors/1/articles',
                'method' => 'GET'
            ],
            [
                'name' => 'authors:view',
                'href' => 'http://example.com/api/authors/1',
                'rel' => '/api/authors/1',
                'method' => 'GET'
            ]
        ];
        $this->assertEquals($links, Hash::get($result, 'links'));
    }

    public function testIndexNested()
    {
        $this->sendRequest('/authors/1/articles', 'GET', ['limit' => 4]);
        $result = $this->getJsonResponse();
        $this->assertSuccess($result);
        $links = [
            [
                'name' => 'self',
                'href' => 'http://example.com/api/authors/1/articles',
                'rel' => '/api/authors/1/articles',
                'method' => 'GET'
            ],
            [
                'name' => 'articles:add',
                'href' => 'http://example.com/api/authors/1/articles',
                'rel' => '/api/authors/1/articles',
                'method' => 'POST'
            ],
            [
                'name' => 'authors:view',
                'href' => 'http://example.com/api/authors/1',
                'rel' => '/api/authors/1',
                'method' => 'GET'
            ]
        ];
        $this->assertEquals($links, Hash::get($result, 'links'));
    }
}
