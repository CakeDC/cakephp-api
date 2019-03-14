<?php
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

use Authentication\AuthenticationService;
use Authentication\Middleware\AuthenticationMiddleware;
use CakeDC\Api\Middleware\ApiMiddleware;
use Cake\Core\Configure;
use Cake\Error\Middleware\ErrorHandlerMiddleware;
use Cake\Http\BaseApplication;
use Cake\Routing\Middleware\AssetMiddleware;
use Cake\Routing\Middleware\RoutingMiddleware;
use CakeDC\Api\Service\ServiceRegistry;

/**
 * Application setup class.
 *
 * This defines the bootstrapping logic and middleware layers you
 * want to use in your application.
 */
class Application extends BaseApplication
{
    public function bootstrap()
    {
        parent::bootstrap();
        ServiceRegistry::getServiceLocator()->clear();

        $this->addPlugin('CakeDC/Users', [
            'path' => ROOT . DS . 'vendor' . DS . 'cakedc' . DS . 'users' . DS
        ]);
        $this->addPlugin('CakeDC/Api', [
            'path' => ROOT . DS
        ]);
    }

    public function pluginBootstrap()
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
    public function middleware($middleware)
    {
        $middleware
            // Catch any exceptions in the lower layers,
            // and make an error page/response
            ->add(new ErrorHandlerMiddleware(Configure::read('Error.exceptionRenderer')))
            // ->add($authentication)
            // Apply Api
            ->add(new ApiMiddleware())
            // Handle plugin/theme assets like CakePHP normally does.
            ->add(new AssetMiddleware())// Apply routing
            ->add(new RoutingMiddleware($this));

        return $middleware;
    }
}
