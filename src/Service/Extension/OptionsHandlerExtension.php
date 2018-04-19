<?php
/**
 * Copyright 2016 - 2018, Cake Development Corporation (http://cakedc.com)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright 2016 - 2018, Cake Development Corporation (http://cakedc.com)
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

namespace CakeDC\Api\Service\Extension;

use CakeDC\Api\Service\Action\DummyAction;
use CakeDC\Api\Service\Action\Result;
use Cake\Event\Event;
use Cake\Event\EventListenerInterface;

/**
 * Class CorsExtension
 *
 * @package CakeDC\Api\Service\Extension
 */
class OptionsHandlerExtension extends Extension implements EventListenerInterface
{

    /**
     * Events supported by this extension.
     *
     * @return array
     */
    public function implementedEvents()
    {
        return [
            'Service.beforeDispatch' => 'onDispatch',
        ];
    }

    /**
     * Cors process before dispatch.
     *
     * @param Event $Event An Event instance
     * @return Result|void
     */
    public function onDispatch(Event $Event)
    {
        $service = $Event->getData('service');
        $request = $service->getRequest();

        if ($request->is('options')) {
            $action = new DummyAction([
                'name' => 'options',
                'service' => $service,
                'route' => null,
                'Extension' => [
                    'CakeDC/Api.Cors'
                ]
            ]);
            $action->Auth->allow('options');
            $result = $service->getResult();
            $result->data($action->process());

            return $result;
        }
    }
}
