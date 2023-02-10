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

use Authentication\Middleware\AuthenticationMiddleware;
use Authorization\Middleware\AuthorizationMiddleware;
use Authorization\Middleware\RequestAuthorizationMiddleware;
use Cake\Core\Configure;
use Cake\Http\Middleware\BodyParserMiddleware;
use Cake\Utility\Hash;
use CakeDC\Api\ApiInitializer;
use CakeDC\Api\Middleware\ParseApiRequestMiddleware;
use CakeDC\Api\Middleware\ProcessApiRequestMiddleware;

Configure::write('Api', []);
$config = Configure::read('Test.Api.Config');

if (empty($config)) {
    $config = [
        'Api' => [
            'renderer' => 'CakeDC/Api.JSend',
            'parser' => 'CakeDC/Api.Form',
            'ServiceFallback' => \CakeDC\Api\Service\FallbackService::class,

            'Jwt' => [
                'AccessToken' => [
                    'lifetime' => 600,
                    'secret' => 'secret',
                ],
                'RefreshToken' => [
                    'lifetime' => 2 * WEEK,
                    'secret' => 'secret',
                ],
            ],

            'Middleware' => [
                'authentication' => [
                    'class' => AuthenticationMiddleware::class,
                    'request' => ApiInitializer::class,
                    'method' => 'getAuthenticationService',
                ],
                'bodyParser' => [
                    'class' => BodyParserMiddleware::class,
                ],
                'apiParser' => [
                    'class' => ParseApiRequestMiddleware::class,
                ],
                'apiAuthorize' => [
                    'class' => AuthorizationMiddleware::class,
                    'request' => ApiInitializer::class,
                    'params' => [
                        'unauthorizedHandler' => 'CakeDC/Api.ApiException',
                    ],
                ],
                'apiAuthorizeRequest' => [
                    'class' => RequestAuthorizationMiddleware::class,
                ],
                'apiProcessor' => [
                    'class' => ProcessApiRequestMiddleware::class,
                ],
            ],

            'Auth' => [
                'Crud' => [
                    'default' => 'auth',
                ],
            ],
            'Service' => [
            ],

            'useVersioning' => false,
            'versionPrefix' => 'v',
        ],
    ];
}

$auth = Configure::read('Test.Api.Auth');
if (!empty($auth)) {
    $config['Api']['Auth'] = $auth;
}

$serviceOptions = Configure::read('Test.Api.Service');
if (!empty($serviceOptions)) {
    $config['Api']['Service'] = Hash::merge($config['Api']['Service'], $serviceOptions);
}

return $config;
