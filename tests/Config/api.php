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

use Cake\Core\Configure;
use Cake\Utility\Hash;

Configure::write('Api', []);
$config = Configure::read('Test.Api.Config');

if (empty($config)) {
    $config = [
        'Api' => [
            'renderer' => 'CakeDC/Api.JSend',
            'parser' => 'CakeDC/Api.Form',
            'ServiceFallback' => \CakeDC\Api\Service\FallbackService::class,

            '2fa' => [
                'enabled' => false,
            ],

            'OneTimePasswordAuthenticator' => [
                'login' => false,
                'checker' => \CakeDC\Api\Service\Auth\TwoFactorAuthentication\DefaultOneTimePasswordAuthenticationChecker::class,
            ],
            'Webauthn2fa' => [
                'checker' => \CakeDC\Api\Service\Auth\TwoFactorAuthentication\DefaultWebauthn2fAuthenticationChecker::class,
                'localhost' => [
                    'enabled' => false,
                    'appName' => 'apilocal',
                    'id' => 'localhost',
                ],
            ],

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
