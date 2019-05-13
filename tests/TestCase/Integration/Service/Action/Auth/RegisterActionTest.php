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

namespace CakeDC\Api\Test\TestCase\Integration\Service\Action\Auth;

use CakeDC\Api\TestSuite\IntegrationTestCase;
use CakeDC\Api\Test\ConfigTrait;
use CakeDC\Api\Test\FixturesTrait;
use CakeDC\Api\Test\Settings;
use Cake\Core\Configure;
use Cake\Utility\Hash;

/**
 * Class RegisterActionTest
 *
 * @package CakeDC\Api\Test\TestCase\Integration\Service\Action\Auth
 */
class RegisterActionTest extends IntegrationTestCase
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
            'message' => 'Please validate your account before log in'
        ];
        $data = $result['data'];
        unset($data['id'], $data['tos_date']);
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
