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
use Cake\Routing\Router;
use Cake\Routing\RouteBuilder;

Router::plugin('CakeDC/Api', ['path' => '/api'], function ($routes) {
    $useVersioning = Configure::read('Api.useVersioning');
    $versionPrefix = Configure::read('Api.versionPrefix');
    $middlewares = Configure::read('Api.Middleware');
    $middlewareNames = array_keys($middlewares);

    $routes->applyMiddleware(...$middlewareNames);

    if (empty($versionPrefix)) {
        $versionPrefix = 'v';
    }
    if ($useVersioning) {
        $routes->connect('/{version}/describe/*', [
                'plugin' => 'CakeDC/Api',
                'controller' => 'Api',
                'action' => 'describe'
            ], ['version' => $versionPrefix . '\d+', 'pass' => []]);
        $routes->connect('/{version}/list/*', [
                'plugin' => 'CakeDC/Api',
                'controller' => 'Api',
                'action' => 'listing'
            ], ['version' => $versionPrefix . '\d+', 'pass' => []]);
    }
    $routes->connect('/describe/*', [
            'plugin' => 'CakeDC/Api',
            'controller' => 'Api',
            'action' => 'describe'
        ]);
    $routes->connect('/list/*', [
            'plugin' => 'CakeDC/Api',
            'controller' => 'Api',
            'action' => 'listing'
        ]);
    $routes->connect('/**', ['plugin' => 'CakeDC/Api', 'controller' => 'Api', 'action' => 'notFound']);
});
