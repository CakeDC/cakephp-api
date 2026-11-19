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

namespace CakeDC\Api\Test;

use Cake\Controller\Controller;
use Cake\Core\Configure;
use Cake\Http\Response;
use Cake\Http\ServerRequest;
use Cake\Utility\Hash;

/**
 * Class ConfigTrait
 *
 * @package CakeDC\Api\Test
 */
trait ConfigTrait
{
    public ?ServerRequest $testRequest = null;

    public ?Response $testResponse = null;

    public $Controller;

    /**
     * Configure public auth access
     */
    protected function publicAccess(): void
    {
        $config = Configure::read('Test.Api');
        $config['Auth'] = [
            'allow' => '*',
        ];
        $config['Service'] = [
                'default' => [
                    'options' => [],
                    'Action' => [
                        'default' => [
                            'Auth' => [
                                'allow' => '*',
                            ],
                        ],
                    ],
                ],
        ];
        Configure::write('Test.Api', $config);
        Configure::load('api');
    }

    /**
     * Configure token auth access
     */
    protected function authAccess(): void
    {
        $config = (array)Configure::read('Test.Api');
        $auth = [
            'authorize' => [
                'CakeDC/Api.Crud' => [],
            ],
            'authenticate' => [
                'all' => [
                    'finder' => 'active',
                ],
                'CakeDC/Api.Form' => [
                    'userModel' => 'CakeDC/Users.Users',
                ],
            ],
        ];
        $path = 'Service.default.Action.default.Auth';
        $config = Hash::insert($config, $path, $auth);
        Configure::write('Test.Api', $config);
    }

    /**
     * Configure token auth access
     */
    protected function tokenAccess(): void
    {
        $config = (array)Configure::read('Test.Api');
        $config['Auth'] = [
            'Crud' => [
                'default' => 'allow',
            ],
        ];

        $auth = [
            'authorize' => [
                'CakeDC/Api.Crud' => [],
            ],
            'authenticate' => [
                'all' => [
                    'finder' => 'auth',
                ],
                'CakeDC/Api.Token' => [
                    'require_ssl' => false,
                    'table' => 'CakeDC/Users.Users',
                ],
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
    protected function addSettingByPath(string $path, $options): void
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
    protected function loadDefaultExtensions($extension, $overwrite = false): void
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
    protected function initializeRequest(array $requestOptions = [], $method = 'GET', array $options = []): void
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
        $requestOptions['environment']['REQUEST_METHOD'] = $method;
        $this->testRequest = new ServerRequest($requestOptions);

        $this->testResponse = empty($options['response']) ? new Response() : $options['response'];
        $this->Controller = $this->createStub(Controller::class);
        $this->Controller->setRequest($this->testRequest);
        $this->Controller->setResponse($this->testResponse);
    }
}
