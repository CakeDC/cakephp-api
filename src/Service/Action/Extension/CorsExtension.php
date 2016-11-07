<?php
/**
 * Copyright 2016, Cake Development Corporation (http://cakedc.com)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright 2016, Cake Development Corporation (http://cakedc.com)
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

namespace CakeDC\Api\Service\Action\Extension;

use CakeDC\Api\Service\Action\Action;
use Cake\Event\Event;
use Cake\Event\EventListenerInterface;

/**
 * Class CorsExtension
 *
 * @package CakeDC\Api\Service\Action\Extension
 */
class CorsExtension extends Extension implements EventListenerInterface
{

    /**
     * Events supported by this extension.
     *
     * @return array
     */
    public function implementedEvents()
    {
        return [
            'Action.beforeProcess' => 'onAction',
        ];
    }

    /**
     * new entity
     *
     * @param Event $Event An Event instance
     * @return void
     */
    public function onAction(Event $Event)
    {
        /** @var Action $action */
        $action = $Event->subject();
        $request = $action->service()->request();
        $response = $action->service()->response();
        $response->cors($request)
             ->allowOrigin($this->config('origin') ?: ['*'])
             ->allowMethods($this->config('methods') ?: ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS', 'HEAD', 'PATCH'])
             ->allowHeaders($this->config('headers') ?: [
                 'X-CSRF-Token',
                 'Content-Type',
                 'Access-Control-Allow-Headers',
                 'Access-Control-Allow-Origin',
                 'Authorization',
                 'X-Requested-With'
             ])
             ->allowCredentials()
             ->maxAge($this->config('maxAge') ?: 300)
             ->build();
    }
}
