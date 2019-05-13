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

class PaginationExtensionTest extends IntegrationTestCase
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
    }

    public function testDefault()
    {
        $this->sendRequest('/articles', 'GET');
        $result = $this->getJsonResponse();
        $expected = [
            'page' => 1,
            'limit' => 20,
            'pages' => 1,
            'count' => 15
        ];
        $this->assertSuccess($result);
        $this->assertEquals($expected, $result['pagination']);
    }

    public function testLimitDefault()
    {
        $this->sendRequest('/articles', 'GET');
        $result = $this->getJsonResponse();
        $this->assertSuccess($result);
        $this->assertEquals(20, $result['pagination']['limit']);
        $this->assertEquals(range(1, 15), Hash::extract($result, 'data.{n}.id'));
    }

    public function testCustomLimit()
    {
        $this->sendRequest('/articles', 'GET', ['limit' => 4]);
        $result = $this->getJsonResponse();
        $expected = [
            'page' => 1,
            'limit' => 4,
            'pages' => 4,
            'count' => 15
        ];
        $this->assertSuccess($result);
        $this->assertEquals($expected, $result['pagination']);
        $this->assertEquals(range(1, 4), Hash::extract($result, 'data.{n}.id'));
    }

    public function testCustomLimitAndPage()
    {
        $this->sendRequest('/articles', 'GET', ['limit' => 4, 'page' => 2]);
        $result = $this->getJsonResponse();
        $expected = [
            'page' => 2,
            'limit' => 4,
            'pages' => 4,
            'count' => 15
        ];
        $this->assertSuccess($result);
        $this->assertEquals($expected, $result['pagination']);
        $this->assertEquals(range(5, 8), Hash::extract($result, 'data.{n}.id'));
    }
}
