<?php
/**
 * Copyright 2016 - 2017, Cake Development Corporation (http://cakedc.com)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright 2016 - 2017, Cake Development Corporation (http://cakedc.com)
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

namespace CakeDC\Api\Test\TestCase\Integration\Service\Action\Auth;

use CakeDC\Api\TestSuite\IntegrationTestCase;
use CakeDC\Api\Test\ConfigTrait;
use CakeDC\Api\Test\FixturesTrait;
use CakeDC\Api\Test\Settings;
use Cake\Core\Configure;
use Cake\ORM\TableRegistry;

/**
 * Class ResetPasswordActionTest
 *
 * @package CakeDC\Api\Test\TestCase\Integration\Service\Action\Auth
 */
class ResetPasswordActionTest extends IntegrationTestCase
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

    public function testSuccessPasswordReset()
    {
        $this->sendRequest('/auth/reset_password_request', 'POST', ['reference' => 'user-1']);
        $result = $this->responseJson();
        $this->assertSuccess($result);
        $this->assertTextEquals('Please check your email to continue with password reset process', $result['data']);

        $Users = TableRegistry::get('CakeDC/Users.Users');
        $user = $Users->find()->where(['id' => Settings::USER1])->enableHydration(false)->first();

        $this->sendRequest('/auth/reset_password', 'POST', [
            'token' => $user['token'],
            'password' => 'new-password',
            'password_confirm' => 'new-password',
        ]);
        $result = $this->responseJson();
        $this->assertSuccess($result);
        $this->assertTextEquals('Password has been changed successfully', $result['data']);

        $this->sendRequest('/auth/login', 'POST', ['username' => 'user-1', 'password' => 'new-password']);
        $result = $this->responseJson();
        $this->assertSuccess($result);
    }
}
