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

use Cake\Console\CommandCollection;
use Cake\Container\ContainerInterface;
use Cake\Core\BasePlugin;
use Cake\Core\Configure;
use Cake\Routing\RouteBuilder;
use CakeDC\Api\Command\ServiceRoutesCommand;
use Closure;
use Override;
use Psr\Http\Server\MiddlewareInterface;

/**
 * Api plugin
 */
class ApiPlugin extends BasePlugin
{
    protected array $middlewares = [];

    /**
     * @inheritDoc
     */
    #[Override]
    public function routes(RouteBuilder $routes): void
    {
        $middlewares = Configure::read('Api.Middleware', []);
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
                    $this->registerMiddleware($routes, $alias, new $class($request, $options));
                } else {
                    $this->registerMiddleware($routes, $alias, new $class($request));
                }
            } elseif (array_key_exists('params', $middleware)) {
                $options = $middleware['params'];
                $this->registerMiddleware($routes, $alias, new $class($options));
            } else {
                $this->registerMiddleware($routes, $alias, new $class());
            }
        }

        parent::routes($routes);
    }

    /**
     * Middleware registrator and holder.
     *
     * @param \Cake\Routing\RouteBuilder $routes Routes.
     * @param string $alias Middleware alias.
     * @param string $class Middleware class instance.
     * @return void
     */
    protected function registerMiddleware(RouteBuilder $routes, string $alias, MiddlewareInterface|Closure|string $class): void
    {
        $routes->registerMiddleware($alias, $class);
        $this->middlewares[$alias] = $class;
    }

    /**
     * Register container services for this plugin.
     *
     * @param \Cake\Container\ContainerInterface $container The container to add services to.
     * @return void
     */
    public function services(ContainerInterface $container): void
    {
        if (array_key_exists('apiParser', $this->middlewares)) {
            $this->middlewares['apiParser']->setContainer($container);
        }
    }

    /**
     * Add console commands for the plugin.
     *
     * @param \Cake\Console\CommandCollection $commands The command collection to update
     * @return \Cake\Console\CommandCollection
     */
    #[Override]
    public function console(CommandCollection $commands): CommandCollection
    {
        return $commands->add('service routes', ServiceRoutesCommand::class);
    }
}
