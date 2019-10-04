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
use Cake\ORM\TableRegistry;
use CakeDC\Api\Test\ConfigTrait;
use CakeDC\Api\Test\Settings;
use CakeDC\Api\TestSuite\IntegrationTestCase;

/**
 * Class ValidateAccountRequestActionTest
 *
 * @package CakeDC\Api\Test\TestCase\Integration\Service\Action\Auth
 */
class ValidateAccountRequestActionTest extends IntegrationTestCase
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

    public function testSuccessValidateAccountRequest()
    {
        $this->sendRequest('/auth/validate_account_request', 'POST', ['reference' => 'user-6']);
        $result = $this->getJsonResponse();
        $this->assertSuccess($result);
        $this->assertTextEquals('Token has been reset successfully. Please check your email.', $result['data']);

        $Users = TableRegistry::get('CakeDC/Users.Users');
        $user = $Users->find()->where(['id' => Settings::USER6])->enableHydration(false)->first();
        $this->assertNotEmpty($user['token']);
    }
}
