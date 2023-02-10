<?php
declare(strict_types=1);

/**
 * Copyright 2018 - 2020, Cake Development Corporation (http://cakedc.com)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright 2018 - 2020, Cake Development Corporation (http://cakedc.com)
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

namespace CakeDC\Api\Test\TestCase\Integration\Service\Action\Auth;

use Cake\Core\Configure;
use Cake\ORM\TableRegistry;
use CakeDC\Api\Test\ConfigTrait;
use CakeDC\Api\TestSuite\IntegrationTestCase;

/**
 * Class LoginActionTest
 *
 * @package CakeDC\Api\Test\TestCase\Integration\Service\Action\Auth
 */
class JwtLoginActionTest extends IntegrationTestCase
{
    use ConfigTrait;

    protected array $fixtures = [
        'plugin.CakeDC/Api.SocialAccounts',
        'plugin.CakeDC/Api.Users',
        'plugin.CakeDC/Api.Articles',
        'plugin.CakeDC/Api.Authors',
        'plugin.CakeDC/Api.Tags',
        'plugin.CakeDC/Api.ArticlesTags',
        'plugin.CakeDC/Api.JwtRefreshTokens',
    ];

    /**
     * setUp
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        TableRegistry::getTableLocator()->clear();
        $this->_authAccess();
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

    public function testSuccessLogin()
    {
        $this->sendRequest('/auth/jwt_login', 'POST', ['username' => 'user-1', 'password' => '12345']);
        $result = $this->getJsonResponse();
        $this->assertSuccess($result);
        $this->assertTrue(is_array($result['data']));
        $this->assertTrue(array_key_exists('access_token', $result['data']));
        $this->assertTrue(array_key_exists('refresh_token', $result['data']));
        $this->assertTrue(array_key_exists('expired', $result['data']));

        $this->sendRequest('/articles', 'GET', ['limit' => 5], null, [
            'Authorization' => $result['data']['access_token'],
        ]);

        $dataResponse = $this->getJsonResponse();
        $this->assertSuccess($dataResponse);

        $this->assertTrue(is_array($dataResponse['data']));
        $this->assertEquals(15, is_countable($dataResponse['data']) ? count($dataResponse['data']) : 0);
    }

    public function testLoginFail()
    {
        $this->sendRequest('/auth/jwt_login', 'POST', ['username' => 'user-1', 'password' => '111']);
        $result = $this->getJsonResponse();
        $this->assertError($result, 401);
        $this->assertErrorMessage($result, 'User not found');
    }
}
