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

$config = [
    'Auth' => [

        'AuthenticationComponent' => [
            'load' => true,
            'loginAction' => [
                'plugin' => 'CakeDC/Users',
                'controller' => 'Users',
                'action' => 'login',
                'prefix' => false,
            ],
            'logoutRedirect' => [
                'plugin' => 'CakeDC/Users',
                'controller' => 'Users',
                'action' => 'login',
                'prefix' => false,
            ],
            'loginRedirect' => '/',
            'requireIdentity' => false,
        ],
        'Authenticators' => [
            'Session' => [
                'className' => 'Authentication.Session',
                'skipTwoFactorVerify' => true,
                'sessionKey' => 'Auth',
            ],
            'Form' => [
                'className' => 'CakeDC/Auth.Form',
                'urlChecker' => 'Authentication.CakeRouter',
                'loginUrl' => [
                    'plugin' => 'CakeDC/Users',
                    'controller' => 'Users',
                    'action' => 'login',
                    'prefix' => false,
                ],
            ],
            'Token' => [
                'className' => 'Authentication.Token',
                'skipTwoFactorVerify' => true,
                'header' => null,
                'queryParam' => 'api_key',
                'tokenPrefix' => null,
            ],
            'Cookie' => [
                'className' => 'CakeDC/Auth.Cookie',
                'skipTwoFactorVerify' => true,
                'rememberMeField' => 'remember_me',
                'cookie' => [
                    'expires' => '1 month',
                    'httpOnly' => true,
                ],
                'urlChecker' => 'Authentication.CakeRouter',
                'loginUrl' => [
                    'plugin' => 'CakeDC/Users',
                    'controller' => 'Users',
                    'action' => 'login',
                    'prefix' => false,
                ],
            ],
            'Social' => [
                'className' => 'CakeDC/Users.Social',
                'skipTwoFactorVerify' => true,
            ],
            'SocialPendingEmail' => [
                'className' => 'CakeDC/Users.SocialPendingEmail',
                'skipTwoFactorVerify' => true,
            ],
        ],
        'Identifiers' => [
            'Password' => [
                'className' => 'Authentication.Password',
                'fields' => [
                    'username' => ['username', 'email'],
                    'password' => 'password',
                ],
                'resolver' => [
                    'className' => 'Authentication.Orm',
                    'finder' => 'active',
                ],
            ],
            'Social' => [
                'className' => 'CakeDC/Users.Social',
                'authFinder' => 'active',
            ],
            'Token' => [
                'className' => 'Authentication.Token',
                'tokenField' => 'api_token',
                'resolver' => [
                    'className' => 'Authentication.Orm',
                    'finder' => 'active',
                ],
            ],
        ],
        'Authorization' => [
            'enable' => true,
            'serviceLoader' => \CakeDC\Users\Loader\AuthorizationServiceLoader::class,
        ],
        'AuthorizationMiddleware' => [
            'unauthorizedHandler' => [
                'exceptions' => [
                    'MissingIdentityException' => \Authorization\Exception\MissingIdentityException::class,
                    'ForbiddenException' => \Authorization\Exception\ForbiddenException::class,
                ],
                'className' => 'Authorization.CakeRedirect',
                'url' => [
                    'plugin' => 'CakeDC/Users',
                    'controller' => 'Users',
                    'action' => 'login',
                ],
            ],
        ],
        'AuthorizationComponent' => [
            'enabled' => true,
        ],
        'RbacPolicy' => [],
        // 'authenticate' => [
            // 'all' => [
                // 'finder' => 'active',
            // ],
            // 'CakeDC/Users.ApiKey' => [
                // 'require_ssl' => false,
            // ],
            // 'CakeDC/Users.RememberMe',
            // 'Form',
        // ],
        // 'authorize' => [
            // 'CakeDC/Users.Superuser',
            // 'CakeDC/Users.SimpleRbac' => [
              // 'permissions' => [
                    // [
                        // 'role' => '*',
                        // 'plugin' => 'CakeDC/Api',
                        // 'controller' => ['Api'],
                        // 'action' => ['process'],
                    // ],
                // ]
            // ],
        // ],
    ],
    'OAuth' => ['path' => null],
];

return $config;
