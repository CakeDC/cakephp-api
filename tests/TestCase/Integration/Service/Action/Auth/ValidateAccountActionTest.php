<?php
/**
 * Copyright 2016 - 2018, Cake Development Corporation (http://cakedc.com)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright 2016 - 2018, Cake Development Corporation (http://cakedc.com)
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
 * Class ValidateAccountActionTest
 *
 * @package CakeDC\Api\Test\TestCase\Integration\Service\Action\Auth
 */
class ValidateAccountActionTest extends IntegrationTestCase
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

    public function testSuccessValidateAccount()
    {
        $this->sendRequest('/auth/login', 'POST', ['username' => 'user-6', 'password' => '12345']);
        $result = $this->getJsonResponse();
        $this->assertError($result, 401);
        $this->assertErrorMessage($result, 'User not found');

        $this->sendRequest('/auth/validate_account', 'POST', ['token' => 'token-6']);
        $result = $this->getJsonResponse();
        $this->assertSuccess($result);
        $this->assertEquals(__d('CakeDC/Api', 'User account validated successfully'), $result['data']);

        $this->sendRequest('/auth/login', 'POST', ['username' => 'user-6', 'password' => '12345']);
        $result = $this->getJsonResponse();
        $this->assertSuccess($result);
    }
}
