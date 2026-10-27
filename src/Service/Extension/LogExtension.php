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
use JsonSerializable;
use Psr\Log\LogLevel;
use stdClass;
use Traversable;

class LogExtension extends Extension implements EventListenerInterface
{
    use LogTrait;

    /**
     * Default settings
     *
     * - `redact`: values of keys that contain one of these words are replaced in the log.
     *
     * @var array
     */
    protected array $_defaultConfig = [
        'redact' => ['password', 'token', 'secret', 'authorization'],
    ];

    protected \CakeDC\Api\Service\Service $_service;

    protected \CakeDC\Api\Service\Action\Action $_action;

    protected ?float $_timer = null;

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
        $this->_service = $event->getData('service');
        $this->_timer = microtime(true);
    }

    /**
     * after process
     *
     * @param \Cake\Event\EventInterface $event An Event instance.
     * @return void
     */
    public function afterProcess(EventInterface $event): void
    {
        $duration = round((microtime(true) - $this->_timer) * 1000, 0);
        $url = $this->_service->getBaseUrl();
        $data = $this->_service->getParser()->getParams();
        $result = $this->_service->getResult()->toArray();
        // the trace of an exception can hold call arguments, such as a password
        unset($result['exceptionStack']);
        $log = [
            'url' => $url,
            'method' => env('REQUEST_METHOD'),
            'duration' => $duration . 'ms',
            'input' => $this->_redact($data),
            'result' => json_encode($this->_redact($result), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
        ];
        $this->log(print_r($log, true), LogLevel::INFO, ['api']);
    }

    /**
     * Replaces sensitive values, such as passwords and tokens, before they are logged.
     *
     * @param mixed $data Request data or result.
     * @return mixed
     */
    protected function _redact($data)
    {
        // a query serializes to a result set, which serializes to its rows
        while ($data instanceof JsonSerializable) {
            $serialized = $data->jsonSerialize();
            if ($serialized === $data) {
                break;
            }
            $data = $serialized;
        }
        if ($data instanceof stdClass) {
            $data = get_object_vars($data);
        } elseif ($data instanceof Traversable) {
            $data = iterator_to_array($data);
        }
        if (!is_array($data)) {
            return $data;
        }
        $words = (array)$this->getConfig('redact');
        foreach ($data as $key => $value) {
            $name = strtolower((string)$key);
            foreach ($words as $word) {
                $word = strtolower((string)$word);
                if ($word !== '' && strpos($name, $word) !== false) {
                    $data[$key] = '***';
                    continue 2;
                }
            }
            $data[$key] = $this->_redact($value);
        }

        return $data;
    }
}
