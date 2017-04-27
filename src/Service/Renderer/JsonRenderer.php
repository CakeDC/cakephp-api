<?php
/**
 * Copyright 2016 - 2017, Cake Development Corporation (http://cakedc.com)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright 2016 - 2017, Cake Development Corporation (http://cakedc.com)
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

namespace CakeDC\Api\Service\Renderer;

use CakeDC\Api\Exception\ValidationException;
use CakeDC\Api\Service\Action\Result;
use Cake\Core\Configure;
use Cake\Utility\Hash;
use Exception;

/**
 * Class JsonRenderer
 * JSON content negotiation Renderer.
 *
 * @package CakeDC\Api\Service\Renderer
 */
class JsonRenderer extends BaseRenderer
{

    /**
     * Builds the HTTP response.
     *
     * @param Result $result The result object returned by the Service.
     * @return bool
     */
    public function response(Result $result = null)
    {
        $response = $this->_service->getResponse();
        $data = $result->data();
        $payload = $result->payload();
        if (is_array($data) && is_array($payload)) {
            $data = Hash::merge($data, $payload);
        }
        $this->_service->setResponse($response->withStringBody($this->_encode($data))->withStatus($result->code())
            ->withType('application/json'));

        return true;
    }

    /**
     * Processes an exception thrown while processing the request.
     *
     * @param Exception $exception The exception object.
     * @return void
     */
    public function error(Exception $exception)
    {
        $response = $this->_service->getResponse();
        $data = [
            'error' => [
                'code' => $exception->getCode(),
                'message' => $this->_buildMessage($exception)
            ]
        ];
        if (Configure::read('debug') > 0) {
            $data['error']['trace'] = $this->_stackTrace($exception);
        }
        if ($exception instanceof ValidationException) {
            $data['error']['validation'] = $exception->getValidationErrors();
        }
        $this->_service->setResponse($response->withStringBody($this->_encode($data))->withType('application/json'));
    }

    /**
     * Encoded object as json. In debug mode used pretty printed objects.
     *
     * @param mixed $data Encoded data.
     * @return string
     */
    protected function _encode($data)
    {
        $format = (Configure::read('debug') > 0) ? JSON_PRETTY_PRINT : 0;

        return json_encode($data, $format);
    }
}
