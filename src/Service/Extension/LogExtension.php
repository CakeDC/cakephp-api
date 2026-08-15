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
use Cake\Log\LogTrait;
use Psr\Log\LogLevel;

class LogExtension extends Extension implements EventListenerInterface
{
    use LogTrait;

    protected \CakeDC\Api\Service\Service $service;

    protected \CakeDC\Api\Service\Action\Action $action;

    protected ?float $timer = null;

    /**
     * Returns a list of events this object is implementing. When the class is registered
     * in an event manager, each individual method will be associated with the respective event.
     *
     * @return array
     */
    public function implementedEvents(): array
    {
        return [
            'Service.beforeDispatch' => 'beforeProcess',
            'Service.afterDispatch' => 'afterProcess',
        ];
    }

    /**
     * before process
     *
     * @param \Cake\Event\EventInterface $event An Event instance.
     * @return void
     */
    public function beforeProcess(EventInterface $event): void
    {
        $this->service = $event->getData('service');
        $this->timer = microtime(true);
    }

    /**
     * after process
     *
     * @param \Cake\Event\EventInterface $event An Event instance.
     * @return void
     */
    public function afterProcess(EventInterface $event): void
    {
        $duration = round((microtime(true) - $this->timer) * 1000, 0);
        $url = $this->service->getBaseUrl();
        $data = $this->service->getParser()->getParams();
        $result = $this->service->getResult()->toArray();
        $log = [
            'url' => $url,
            'method' => env('REQUEST_METHOD'),
            'duration' => $duration . 'ms',
            'input' => $data,
            'result' => json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
        ];
        $this->log(print_r($log, true), LogLevel::INFO, ['api']);
    }
}
