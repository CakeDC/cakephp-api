<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link      http://cakephp.org CakePHP(tm) Project
 * @since     3.3.0
 * @license   http://www.opensource.org/licenses/mit-license.php MIT License
 */

namespace CakeDC\Api\Test\App;

use Cake\Core\Configure;
use Cake\Core\ContainerInterface;
use Cake\Error\Middleware\ErrorHandlerMiddleware;
use Cake\Http\BaseApplication;
use Cake\Http\MiddlewareQueue;
use Cake\Routing\Middleware\AssetMiddleware;
use Cake\Routing\Middleware\RoutingMiddleware;
use CakeDC\Api\Middleware\ContainerInjectorMiddleware;
use CakeDC\Api\Service\ServiceRegistry;
use CakeDC\Api\Test\App\DI\Service\TestService;

/**
 * Application setup class.
 *
 * This defines the bootstrapping logic and middleware layers you
 * want to use in your application.
 */
class Application extends BaseApplication
{
    public function bootstrap(): void
    {
        parent::bootstrap();
        ServiceRegistry::getServiceLocator()->clear();

        $this->addPlugin('CakeDC/Users', [
            'path' => ROOT . DS . 'vendor' . DS . 'cakedc' . DS . 'users' . DS,
        ]);
        $this->addPlugin('CakeDC/Api', [
            'path' => ROOT . DS,
        ]);
    }

    public function pluginBootstrap(): void
    {
        parent::pluginBootstrap();

        Configure::load('api');
        Configure::write('Users.config', ['users']);
    }

    /**
     * Setup the middleware your application will use.
     *
     * @param \Cake\Http\MiddlewareQueue $middleware The middleware queue to setup.
     * @return \Cake\Http\MiddlewareQueue The updated middleware.
     */
    public function middleware(MiddlewareQueue $middleware): MiddlewareQueue
    {
        $middleware
            // Catch any exceptions in the lower layers,
            // and make an error page/response
            ->add(new ErrorHandlerMiddleware([]))
            // ->add($authentication)
            // Apply Api
            ->add(new ContainerInjectorMiddleware($this->getContainer()))
            // ->add(new ParseApiRequestMiddleware())
            // ->add(new ProcessApiRequestMiddleware())
            // Handle plugin/theme assets like CakePHP normally does.
            ->add(new AssetMiddleware())// Apply routing
            ->add(new RoutingMiddleware($this));

        return $middleware;
    }

    /**
     * @inheritDoc
     */
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

    public function services(ContainerInterface $container): void
    {
        $container->add(TestService::class);
    }
}
