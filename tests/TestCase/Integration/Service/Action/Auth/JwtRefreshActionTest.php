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
use CakeDC\Api\Service\ServiceRegistry;
use CakeDC\Api\Test\ConfigTrait;
use CakeDC\Api\TestSuite\IntegrationTestCase;

/**
 * Class RefreshActionTest
 *
 * @package CakeDC\Api\Test\TestCase\Integration\Service\Action\Auth
 */
class JwtRefreshActionTest extends IntegrationTestCase
{
    use ConfigTrait;

    public $fixtures = [
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

    public function testSuccessRefresh()
    {
        $this->sendRequest('/auth/jwt_login', 'POST', ['username' => 'user-1', 'password' => '12345']);
        $result = $this->getJsonResponse();
        $this->assertSuccess($result);
        $this->assertTrue(is_array($result['data']));
        $this->assertTrue(array_key_exists('refresh_token', $result['data']));
        sleep(1);

        ServiceRegistry::getServiceLocator()->clear();
        $this->sendRequest('/auth/jwt_refresh', 'POST', [], null, [
            'Authorization' => $result['data']['refresh_token'],
        ]);
        $result = $this->getJsonResponse();
        $this->assertSuccess($result);
    }
}
