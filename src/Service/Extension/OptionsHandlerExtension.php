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

namespace CakeDC\Api\Service\Extension;

use Cake\Event\EventInterface;
use Cake\Event\EventListenerInterface;
use CakeDC\Api\Service\Action\Result;
use Cake\Http\ServerRequest;
use CakeDC\Api\Service\Action\DummyAction;
use CakeDC\Api\Service\Service;

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
    public function implementedEvents(): array
    {
        return [
            'Service.beforeDispatch' => 'onDispatch',
        ];
    }

    /**
     * Cors process before dispatch.
     *
     * @param \Cake\Event\EventInterface $event An Event instance
     * @return \CakeDC\Api\Service\Action\Result|null
     * @throws \Exception
     */
    public function onDispatch(EventInterface $event): ?Result
    {
        /** @var Service $service */
        $service = $event->getData('service');
        /** @var ServerRequest $request */
        $request = $service->getRequest();

        if ($request->is('options')) {
            $action = new DummyAction([
                'name' => 'options',
                'service' => $service,
                'route' => null,
                'Extension' => [
                    'CakeDC/Api.Cors',
                ],
            ]);
            $action->Auth->allow('options');
            $result = $service->getResult();
            $result->setData($action->process());

            return $result;
        }

        return null;
    }
}
