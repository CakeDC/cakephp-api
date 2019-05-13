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

namespace CakeDC\Api\TestSuite;

use Cake\TestSuite\IntegrationTestTrait;
use Cake\View\Exception\MissingTemplateException;
use CakeDC\Api\Service\ServiceRegistry;
use Cake\Core\Configure;
use Cake\Datasource\EntityInterface;
use Cake\ORM\TableRegistry;
use Cake\Utility\Hash;

/**
 * Class IntegrationTestCase
 *
 * @package CakeDC\Api\TestSuite
 */
class IntegrationTestCase extends \Cake\TestSuite\TestCase
{
    use IntegrationTestTrait;

    /**
     * @var string|int Current logged in user
     */
    protected $_defaultUserId;

    /**
     * setUp
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        Configure::write('Api', []);
    }

    /**
     * tearDown
     *
     * @return void
     */
    public function tearDown(): void
    {
        parent::tearDown();
        ServiceRegistry::getServiceLocator()->clear();
    }

    /**
     * Default user api method.
     *
     * @param string $userId User id.
     * @return string
     */
    public function getDefaultUser($userId = null)
    {
        if ($userId === null) {
            $userId = $this->_defaultUserId;
        } else {
            $this->_defaultUserId = $userId;
        }

        return $userId;
    }

    /**
     * Returns user token.
     *
     * @param string $userId User id.
     * @return mixed|null
     */
    protected function _userToken($userId = null)
    {
        if ($userId === null) {
            $userId = $this->getDefaultUser();
        }
        $Users = TableRegistry::getTableLocator()->get('CakeDC/Users.Users');
        $user = $Users->find()->where(['id' => $userId])->first();
        if ($user instanceof EntityInterface) {
            return $user['api_token'];
        }

        return null;
    }

    /**
     * Send api request.
     *
     * @param string $url Url.
     * @param string $method HTTP method.
     * @param array $data Api parameters.
     * @param string $userId Current user id.
     * @return void
     * @throws \PHPUnit\Exception
     * @throws \Throwable
     */
    public function sendRequest($url, $method, $data = [], $userId = null)
    {
        ServiceRegistry::getServiceLocator()->clear();
        $userToken = $this->_userToken($userId);

        Configure::load('api');

        if (!is_string($url)) {
            $this->_sendRequest($url, $method, $data);

            return;
        }
        $url = '/api' . $url;
        if (is_string($url)) {
            if ($userToken !== null) {
                $url = $this->_appendGetParam($url, 'token', $userToken);
            }
        }
        if ($method == 'GET' && is_string($url)) {
            if (!empty($data)) {
                foreach ($data as $key => $value) {
                    if (!is_array($value)) {
                        $url = $this->_appendGetParam($url, $key, $value);
                    }
                }
            }
        }
        //$this->useHttpServer(true);
        try {
            ServiceRegistry::getServiceLocator()->clear();
            TableRegistry::getTableLocator()->clear();
            $this->_sendRequest($url, $method, $data);
        } catch (MissingTemplateException $ex) {
            throw new MissingTemplateException(sprintf('Possibly related to %s', $this->_exception->getMessage()), [], 500, $ex);
        }
    }

    /**
     * Add param to request.
     *
     * @param string $url Url.
     * @param string $key Param name.
     * @param string $value Param value.
     * @return string
     */
    protected function _appendGetParam($url, $key, $value)
    {
        if (strpos($url, '?') !== false) {
            $appendChar = '&';
        } else {
            $appendChar = '?';
        }

        return $url . $appendChar . urlencode($key) . '=' . urlencode($value);
    }

    /**
     * Assert result is success.
     *
     * @param array $result Response.
     * @return void
     */
    public function assertSuccess($result)
    {
        $this->assertTrue(is_array($result));
        $this->assertEquals($result['status'], 'success');
        $this->assertEquals(200, $this->_response->getStatusCode());
    }

    /**
     * @return mixed
     */
    public function getJsonResponse()
    {
        return json_decode((string)$this->_response->getBody(), true);
    }

    /**
     * Assert result is error.
     *
     * @param array $result Response.
     * @param int $code Result code.
     * @return void
     */
    public function assertError($result, $code = null)
    {
        $this->assertTrue(is_array($result));
        $this->assertEquals($result['status'], 'error');
        $this->assertEquals(200, $this->_response->getStatusCode());
        if (!empty($code)) {
            $this->assertEquals($code, $result['code']);
        }
    }

    /**
     * Helper method for status assertions.
     *
     * @param int $code Status code.
     * @param string $message The error message.
     * @return void
     */
    public function assertStatus($code, $message = null)
    {
        if ($message === null) {
            $message = "Status code $code does not match";
        }
        $this->_assertStatus($code, $code, $message);
    }

    /**
     * Assert error message.
     *
     * @param array $result Response.
     * @param string $expectedMessage Message.
     * @return void
     */
    public function assertErrorMessage($result, $expectedMessage)
    {
        $message = Hash::get($result, 'message');
        $this->assertTrue(is_string($message) && strpos($message, $expectedMessage) === 0);
    }
}
