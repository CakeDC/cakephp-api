<?php
declare(strict_types=1);

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

use Cake\Core\Configure;
use Cake\Utility\Hash;
use CakeDC\Api\Test\ConfigTrait;
use CakeDC\Api\Test\Settings;
use CakeDC\Api\TestSuite\IntegrationTestCase;

class ExtendedSortExtensionTest extends IntegrationTestCase
{
    use ConfigTrait;

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
        $this->_loadDefaultExtensions('CakeDC/Api.ExtendedSort');
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

    public function testDefault()
    {
        $this->sendRequest('/articles', 'GET');
        $result = $this->getJsonResponse();
        $expected = [
            'page' => 1,
            'limit' => 20,
            'pages' => 1,
            'count' => 15,
        ];
        $this->assertSuccess($result);
        $this->assertEquals($expected, $result['pagination']);
    }

    public function testSortById()
    {
        $this->sendRequest('/authors', 'GET', ['limit' => 4, 'sort' => json_encode(['id' => 'asc'], JSON_THROW_ON_ERROR)]);
        $result = $this->getJsonResponse();
        $this->assertSuccess($result);
        $this->assertEquals(range(1, 4), Hash::extract($result, 'data.{n}.id'));
    }

    public function testSortByIdDesc()
    {
        $this->sendRequest('/authors', 'GET', ['limit' => 4, 'sort' => json_encode(['id' => 'desc'], JSON_THROW_ON_ERROR)]);
        $result = $this->getJsonResponse();
        $this->assertSuccess($result);
        $this->assertEquals(array_reverse(range(12, 15)), Hash::extract($result, 'data.{n}.id'));
    }

    public function testSortByName()
    {
        $this->sendRequest('/authors', 'GET', ['limit' => 4, 'sort' => json_encode(['first_name' => 'asc'], JSON_THROW_ON_ERROR)]);
        $result = $this->getJsonResponse();
        $this->assertSuccess($result);
        $this->assertEquals([7, 5, 11, 15], Hash::extract($result, 'data.{n}.id'));

        $this->sendRequest('/authors', 'GET', ['limit' => 4, 'sort' => json_encode(['first_name' => 'asc', 'last_name' => 'asc'], JSON_THROW_ON_ERROR)]);
        $result = $this->getJsonResponse();
        $this->assertSuccess($result);
        $this->assertEquals([7, 5, 11, 15], Hash::extract($result, 'data.{n}.id'));
    }

    public function testSortByFirstNameDesc()
    {
        $this->sendRequest('/authors', 'GET', ['limit' => 4, 'sort' => json_encode(['first_name' => 'desc'], JSON_THROW_ON_ERROR)]);

        $result = $this->getJsonResponse();
        $this->assertSuccess($result);
        $this->assertEquals([4, 9, 14, 10], Hash::extract($result, 'data.{n}.id'));
    }
}
