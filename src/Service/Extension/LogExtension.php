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

namespace CakeDC\Api\Service\Extension;

use CakeDC\Api\Service\Action\Action;
use CakeDC\Api\Service\Service;
use Cake\Event\Event;
use Cake\Event\EventListenerInterface;
use Cake\Log\LogTrait;

class LogExtension extends Extension implements EventListenerInterface
{
    use LogTrait;

    /**
     * @var Service
     */
    protected $_service;

    /**
     * @var Action
     */
    protected $_action;

    /**
     * @var int
     */
    protected $_timer;

    /**
     * Returns a list of events this object is implementing. When the class is registered
     * in an event manager, each individual method will be associated with the respective event.
     *
     * @return array
     */
    public function implementedEvents()
    {
        return [
            'Service.beforeDispatch' => 'beforeProcess',
            'Service.afterDispatch' => 'afterProcess',
        ];
    }

    /**
     * before process
     *
     * @param Event $event An Event instance.
     * @return void
     */
    public function beforeProcess(Event $event)
    {
        $this->_service = $event->data['service'];
        $this->_timer = microtime(true);
    }

    /**
     * after process
     *
     * @param Event $event An Event instance.
     * @return void
     */
    public function afterProcess(Event $event)
    {
        $duration = round((microtime(true) - $this->_timer) * 1000, 0);
        $url = $this->_service->baseUrl();
        $data = $this->_service->parser()->params();
        $result = $this->_service->result()->toArray();
        $log = [
            'url' => $url,
            'method' => env('REQUEST_METHOD'),
            'duration' => $duration . 'ms',
            'input' => $data,
            'result' => json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
        ];
        $this->log($log);
    }
}
