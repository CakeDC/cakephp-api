<?php
/**
 * Copyright 2016 - 2017, Cake Development Corporation (http://cakedc.com)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright 2016 - 2017, Cake Development Corporation (http://cakedc.com)
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

$config = [
    'Auth' => [
        'authenticate' => [
            'all' => [
                'finder' => 'active',
            ],
            'CakeDC/Users.ApiKey' => [
                'require_ssl' => false,
            ],
            'CakeDC/Users.RememberMe',
            'Form',
        ],
        'authorize' => [
            'CakeDC/Users.Superuser',
            'CakeDC/Users.SimpleRbac' => [
              'permissions' => [
                    [
                        'role' => '*',
                        'plugin' => 'CakeDC/Api',
                        'controller' => ['Api'],
                        'action' => ['process'],
                    ],
                ]
            ],
        ],
    ],
    'OAuth' => ['path' => null],
];

return $config;
