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
use Authentication\AuthenticationService;
use Authentication\Middleware\AuthenticationMiddleware;
use Authorization\Middleware\AuthorizationMiddleware;
use Authorization\Middleware\RequestAuthorizationMiddleware;
use Cake\Http\Middleware\BodyParserMiddleware;
use CakeDC\Api\Middleware\ParseApiRequestMiddleware;
use CakeDC\Api\Middleware\ProcessApiRequestMiddleware;
use CakeDC\Api\ApiInitializer;

return [
    'Api' => [
        // if service class is not defined we use crud fallback service
        'ServiceFallback' => '\\CakeDC\\Api\\Service\\FallbackService',
        // response rendered as JSend
        'renderer' => 'CakeDC/Api.JSend',
        // Data parse from cakephp request object
        'parser' => 'CakeDC/Api.Form',

        //routes inflector: specify underscore, dasherize, or false for neither/no inflection
        'routesInflectorMethod' => false,

        // version is not used
        'useVersioning' => false,
        'versionPrefix' => 'v',
        'serviceLookupPlugins' => null,
        'lookupMode' => 'underscore',

        // auth permission uses require auth strategy
        'Auth' => [
            'Crud' => [
                'default' => 'auth'
            ],
        ],

        'Jwt' => [
            'enabled' => false,
            'AccessToken' => [
                'lifetime' => 600,
                'secret' => '',
            ],
            'RefreshToken' => [
                'lifetime' => 2 * WEEK,
                'secret' => '',
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

        'Service' => [
            'default' => [
                'options' => [],
                'Action' => [
                    'default' => [
                        //auth configuration
                        'Auth' => [
                            'allow' => '*', // PUBLIC ACCESS for all API endpoints, remove this line to secure
                            'authorize' => [
                                'CakeDC/Api.Crud' => []
                            ],
                            'authenticate' => [
                                'CakeDC/Api.Token' => [
                                    'require_ssl' => false,
                                ]
                            ],
                        ],
                        // default app extensions
                        'Extension' => [
                            // allow request from other domains
                            'CakeDC/Api.Cors',
                            // enable sort
                            'CakeDC/Api.Sort',
                            // load Hateoas
                            'CakeDC/Api.CrudHateoas',
                            // enable relations
                            'CakeDC/Api.CrudRelations',
                        ]
                    ],
                    // all index actions configuration
                    'Index' => [
                        'Extension' => [
                            // enable pagination for index actions
                            'CakeDC/Api.Paginate',
                        ],
                    ],
                ],
            ],
        ],
        'Log' => [
            'className' => 'File',
            'scopes' => ['api'],
            'levels' => ['error', 'info'],
            'file' => 'api.log',
        ],
        'Flysystem' => [
            'expire' => '+1 day'
        ]
    ]
];
