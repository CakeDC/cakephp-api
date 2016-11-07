<?php
/**
 * Copyright 2016, Cake Development Corporation (http://cakedc.com)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright 2016, Cake Development Corporation (http://cakedc.com)
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

namespace CakeDC\Api\Test\TestCase\Integration\Service\Action\Auth;

use CakeDC\Api\TestSuite\IntegrationTestCase;
use CakeDC\Api\Test\ConfigTrait;
use CakeDC\Api\Test\FixturesTrait;
use CakeDC\Api\Test\Settings;
use Cake\Core\Configure;
use Cake\Utility\Hash;

/**
 * Class LoginActionTest
 *
 * @package CakeDC\Api\Test\TestCase\Integration\Service\Action\Auth
 */
class LoginActionTest extends IntegrationTestCase
{

    use ConfigTrait;
    use FixturesTrait;

    /**
     * setUp
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        $this->_authAccess();
        Configure::write('App.fullBaseUrl', 'http://example.com');
    }

    /**
     * tearDown
     *
     * @return void
     */
    public function tearDown()
    {
        parent::tearDown();
        Configure::write('Test.Api.Extension', null);
    }

    public function testSuccessLogin()
    {
        $this->sendRequest('/auth/login', 'POST', ['username' => 'user-1', 'password' => '12345']);
        $result = $this->responseJson();
        $this->assertSuccess($result);
        $expected = [
            'id' => '00000000-0000-0000-0000-000000000001',
            'username' => 'user-1',
            'email' => 'user-1@test.com',
            'first_name' => 'first1',
            'last_name' => 'last1',
            'activation_date' => '2015-06-24T17:33:54+03:00',
            'tos_date' => '2015-06-24T17:33:54+03:00',
            'active' => true,
            'api_token' => 'yyy'
        ];
        $this->assertEquals($expected, Hash::get($result, 'data'));
    }

    public function testLoginFail()
    {
        $this->sendRequest('/auth/login', 'POST', ['username' => 'user-1', 'password' => '111']);
        $result = $this->responseJson();
        $this->assertError($result, 404);
        $this->assertErrorMessage($result, 'User not found');
    }
}
