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

namespace CakeDC\Api\Test;

use Cake\Core\Configure;
use Cake\Network\Request;
use Cake\Network\Response;
use Cake\Utility\Hash;

/**
 * Class ConfigTrait
 *
 * @package CakeDC\Api\Test
 */
trait ConfigTrait
{

    /**
     * Configure public auth access
     */
    protected function _publicAccess()
    {
        $config = Configure::read('Test.Api');
        $config['Auth'] = [
            'allow' => '*'
        ];
        Configure::write('Test.Api', $config);
    }

    /**
     * Configure token auth access
     */
    protected function _authAccess()
    {
        $config = (array)Configure::read('Test.Api');
        $auth = [
            'authorize' => [
                'CakeDC/Api.Crud' => []
            ],
            'authenticate' => [
                'all' => [
                    'finder' => 'active',
                ],
                'CakeDC/Api.Form' => [
                    'userModel' => 'CakeDC/Users.Users'
                ]
            ],
        ];
        $path = 'Service.default.Action.default.Auth';
        $config = Hash::insert($config, $path, $auth);
        Configure::write('Test.Api', $config);
    }

    /**
     * Configure token auth access
     */
    protected function _tokenAccess()
    {
        $config = (array)Configure::read('Test.Api');
        $config['Auth'] = [
            'Crud' => [
                'default' => 'allow'
            ],
        ];

        $auth = [
            'authorize' => [
                'CakeDC/Api.Crud' => []
            ],
            'authenticate' => [
                'all' => [
                    'finder' => 'auth',
                ],
                'CakeDC/Api.Token' => [
                    'require_ssl' => false,
                    'table' => 'CakeDC/Users.Users',
                ]
            ],
        ];
        $path = 'Service.default.Action.default.Auth';
        $config = Hash::insert($config, $path, $auth);
        Configure::write('Test.Api', $config);
    }

    /**
     * Insert api options into specific path.
     *
     * @param string $path Setting path.
     * @param mixed $options An options.
     * @return void
     */
    protected function _addSettingByPath($path, $options)
    {
        $config = (array)Configure::read('Test.Api');
        $config = Hash::insert($config, $path, $options);
        Configure::write('Test.Api', $config);
    }

    /**
     * Add default extensions into configuration.
     *
     * @param array|string $extension
     * @param bool $overwrite Owerwrite flag.
     */
    protected function _loadDefaultExtensions($extension, $overwrite = false)
    {
        $config = (array)Configure::read('Test.Api');
        $path = 'Service.default.Action.default.Extension';
        $default = (array)Hash::get($config, $path);
        $config = Hash::insert($config, $path, ($overwrite ? $extension : array_merge($default, (array)$extension)));
        Configure::write('Test.Api', $config);
    }

    /**
     * Performs controller initialization.
     *
     * @param array $requestOptions Request options.
     * @param string $method Http method.
     * @param array $options Options.
     * @return void
     */
    protected function _initializeController($requestOptions = [], $method = 'GET', $options = [])
    {
        $_SERVER['REQUEST_METHOD'] = $method;
        if (empty($requestOptions['params'])) {
            $requestOptions['params'] = [];
        }
        if (empty($requestOptions['params']['service'])) {
            $requestOptions['params']['service'] = 'articles';
        }
        if (empty($requestOptions['params']['pass'])) {
            $requestOptions['params']['pass'] = [];
        }
        $this->request = new Request($requestOptions);

        if (empty($options['response'])) {
            $this->response = new Response();
        } else {
            $this->response = $options['response'];
        }
        $this->Controller = $this->createMock('Cake\Controller\Controller', ['redirect']);
        $this->Controller->request = $this->request;
        $this->Controller->response = $this->response;
    }
}
