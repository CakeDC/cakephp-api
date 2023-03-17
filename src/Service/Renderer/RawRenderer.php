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
use Exception;

/**
 * Class RawRenderer
 * Raw unformatted content negotiation Renderer.
 *
 * @package CakeDC\Api\Service\Renderer
 */
class RawRenderer extends BaseRenderer
{
    /**
     * Builds the HTTP response.
     *
     * @param \CakeDC\Api\Service\Action\Result $result The result object returned by the Service.
     * @return bool
     */
    public function response(?Result $result = null): bool
    {
        $response = $this->_service->getResponse();
        $data = $result->getData();
        if (is_array($data)) {
            $body = print_r($data, true);
        } else {
            $body = (string)$data;
        }
        $response = $response->withStringBody($body)
              ->withStatus($result->getCode())
              ->withType('text/plain');
        $this->_service->setResponse($response);

        return true;
    }

    /**
     * Processes an exception thrown while processing the request.
     *
     * @param \Exception $exception The exception object.
     * @return void
     */
    public function error(Exception $exception): void
    {
        $response = $this->_service->getResponse();
        $message = $exception->getMessage();
        if (Configure::read('debug')) {
            $message .= ' on line ' . $exception->getLine() . ' in ' . $exception->getFile();
        }
        $trace = $exception->getTrace();
        $debug = Configure::read('debug') ? "\n" . print_r($trace, true) : '';
        $this->_service->setResponse($response->withStringBody($message . $debug)->withType('text/plain'));
    }
}
