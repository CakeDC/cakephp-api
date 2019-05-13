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

class NestedCrudExtensionTest extends IntegrationTestCase
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
        $this->_loadDefaultExtensions([]);
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

    public function testIndex()
    {
        $this->sendRequest('/authors/1/articles', 'GET');
        $result = $this->getJsonResponse();
        $this->assertSuccess($result);
        $this->assertEquals([1, 10, 13, 15], Hash::extract($result['data'], '{n}.id'));
    }

    public function testView()
    {
        $this->sendRequest('/authors/1/articles/1', 'GET');
        $result = $this->getJsonResponse();
        $this->assertSuccess($result);
        $article = [
            'id' => 1,
            'author_id' => 1,
            'title' => 'First Article',
            'body' => 'First Article Body',
            'published' => 'Y'
        ];
        $this->assertEquals($article, $result['data']);

        $this->sendRequest('/authors/1/articles/2', 'GET');
        $result = $this->getJsonResponse();
        $this->assertError($result, 404);
        $this->assertNull($result['data']);
    }

    public function testAdd()
    {
        $article = [
            'title' => 'New Article',
            'body' => 'New Article Body',
            'published' => 'Y'
        ];
        $this->sendRequest('/authors/1/articles', 'POST', $article);
        $result = $this->getJsonResponse();
        $this->assertSuccess($result);
        $this->assertArrayHasKey('id', $result['data']);
        $id = $result['data']['id'];

        $this->sendRequest('/authors/1/articles/' . $id, 'GET');
        $result = $this->getJsonResponse();
        $this->assertSuccess($result);

        $res = array_intersect_key($article, $result['data']);
        $this->assertEquals($article, $res);
    }

    public function testEdit()
    {
        $article = [
            'title' => 'Article 1',
            'body' => 'Article 1 Body',
            'published' => 'Y'
        ];
        $this->sendRequest('/authors/1/articles/1', 'PUT', $article);
        $result = $this->getJsonResponse();
        $this->assertSuccess($result);
        $this->assertArrayHasKey('id', $result['data']);
        $this->assertEquals(1, $result['data']['id']);

        $this->sendRequest('/authors/1/articles/1', 'GET');
        $result = $this->getJsonResponse();
        $this->assertSuccess($result);

        $res = array_intersect_key($article, $result['data']);
        $this->assertEquals($article, $res);
    }

    public function testEditOwnedByOtherAuthor()
    {
        $article = [
            'title' => 'Article 1',
            'body' => 'Article 1 Body',
            'published' => 'Y'
        ];
        $this->sendRequest('/authors/1/articles/2', 'PUT', $article);
        $result = $this->getJsonResponse();
        $this->assertError($result, 404);
        $this->assertNull($result['data']);
    }

    public function testDelete()
    {
        $this->sendRequest('/authors/1/articles/1', 'DELETE');
        $result = $this->getJsonResponse();
        $this->assertSuccess($result);

        $this->sendRequest('/authors/1/articles/1', 'GET');
        $result = $this->getJsonResponse();
        $this->assertError($result, 404);

        $this->sendRequest('/authors/1/articles/2', 'DELETE');
        $result = $this->getJsonResponse();
        $this->assertError($result, 404);
    }
}
