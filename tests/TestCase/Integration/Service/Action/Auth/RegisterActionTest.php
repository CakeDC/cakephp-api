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

namespace CakeDC\Api\Test\TestCase\Integration\Service\Action\Auth;

use Cake\Core\Configure;
use Cake\Routing\Router;
use Cake\Utility\Hash;
use CakeDC\Api\Test\ConfigTrait;
use CakeDC\Api\TestSuite\IntegrationTestCase;

/**
 * Class RegisterActionTest
 *
 * @package CakeDC\Api\Test\TestCase\Integration\Service\Action\Auth
 */
class RegisterActionTest extends IntegrationTestCase
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
        $builder = Router::createRouteBuilder('/', []);
        $builder->connect('/users/validate-email/*', [
             'plugin' => 'CakeDC/Users',
             'controller' => 'Users',
             'action' => 'validateEmail',
         ]);
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

    public function testSuccessRegister()
    {
        $this->sendRequest('/auth/register', 'POST', [
            'username' => 'user-100',
            'email' => 'user100@user.com',
            'password' => '12345678',
            'password_confirm' => '12345678',
            'first_name' => 'first 100',
            'last_name' => 'last 100',
            'tos' => true,
        ]);
        $result = $this->getJsonResponse();
        $this->assertSuccess($result);
        $expected = [
            'username' => 'user-100',
            'email' => 'user100@user.com',
            'first_name' => 'first 100',
            'last_name' => 'last 100',
            'activation_date' => null,
            'active' => false,
            'api_token' => null,
            'message' => 'Please validate your account before log in',
            'role' => 'user',
        ];
        $data = $result['data'];
        unset($data['id'], $data['tos_date'], $data['last_login'], $data['secret_verified']);
        $this->assertEquals($expected, $data);
    }

    public function testRegisterValidationFail()
    {
        $this->sendRequest('/auth/register', 'POST', ['username' => 'user-100']);
        $result = $this->getJsonResponse();
        $this->assertError($result, 422);
        $this->assertEquals(['password', 'password_confirm', 'tos'], array_keys(Hash::get($result, 'data')));
        $this->assertErrorMessage($result, 'Validation failed');
    }
}
