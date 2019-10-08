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

namespace CakeDC\Api;

use Authentication\AuthenticationService;
use Authentication\Middleware\AuthenticationMiddleware;
use Authorization\Middleware\AuthorizationMiddleware;
use Authorization\Middleware\RequestAuthorizationMiddleware;
use CakeDC\Api\Middleware\ParseApiRequestMiddleware;
use CakeDC\Api\Middleware\ProcessApiRequestMiddleware;
use Cake\Core\BasePlugin;
use Cake\Core\Configure;

/**
 * Api plugin
 */
class Plugin extends BasePlugin
{
    
    public function routes($routes): void
    {
        $middlewares = Configure::read('Api.Middleware');
        foreach ($middlewares as $alias => $middleware) {
            $class = $middleware['class'];
            if (array_key_exists('request', $middleware)) {
                $requestClass = $middleware['request'];
                $request = new $requestClass();
                if (array_key_exists('method', $middleware)) {
                    $request = $request->{$middleware['method']}();
                }
                if (array_key_exists('params', $middleware)) {
                    $options = $middleware['params'];
                    $routes->registerMiddleware($alias, new $class($request, $options));
                } else {
                    $routes->registerMiddleware($alias, new $class($request));
                }
            } else {
                $routes->registerMiddleware($alias, new $class());
            }
        }

        parent::routes($routes);
    }    
    
}
