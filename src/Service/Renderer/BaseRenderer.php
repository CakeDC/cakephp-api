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

namespace CakeDC\Api\Service\Renderer;

use Cake\Core\Configure;
use CakeDC\Api\Service\Action\Result;
use CakeDC\Api\Service\Service;
use Exception;

/**
 * Base class for a Service content negotiation Renderer.
 */
abstract class BaseRenderer
{
    /**
     * Reference to the Service.
     */
    protected ?\CakeDC\Api\Service\Service $_service = null;

    /**
     * Constructor
     *
     * @param \CakeDC\Api\Service\Service $service The Service instantiating the Renderer.
     */
    public function __construct(Service $service)
    {
        $this->_service = $service;
    }

    /**
     * Confirms if the specified content type is acceptable for the response.
     *
     * @return bool
     */
    public function accept(): bool
    {
        return true;
    }

    /**
     * Builds the HTTP response.
     *
     * @param \CakeDC\Api\Service\Action\Result $result The result object returned by the Service.
     * @return bool
     */
    abstract public function response(?Result $result = null): bool;

    /**
     * Processes an exception thrown while processing the request.
     *
     * @param \Exception $exception The exception object.
     * @return void
     */
    abstract public function error(Exception $exception): void;

    /**
     * Format error message.
     *
     * @param \Exception $exception An Exception instance.
     * @return string
     */
    protected function _buildMessage(Exception $exception): string
    {
        $message = $exception->getMessage();
        if (Configure::read('debug') > 0) {
            $message .= ' on line ' . $exception->getLine() . ' in ' . $exception->getFile();
        }

        return $message;
    }

    /**
     * Returns formatted stack trace
     *
     * @param \Exception $exception An Exception instance.
     * @return array|null
     */
    protected function _stackTrace(Exception $exception): ?array
    {
        if (Configure::read('debug') == 0) {
            return null;
        }
        $trace = $exception->getTrace();
        $count = count($trace);
        for ($i = 0; $i < $count; $i++) {
            foreach (array_keys($trace[$i]) as $key) {
                if ($key === 'object' || $key === 'type' || $key === 'args') {
                    unset($trace[$i][$key]);
                }
            }
        }

        return $trace;
    }
}
