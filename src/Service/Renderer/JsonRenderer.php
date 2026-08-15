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
use Cake\Utility\Hash;
use CakeDC\Api\Exception\ValidationException;
use CakeDC\Api\Service\Action\Result;
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
     * @param \CakeDC\Api\Service\Action\Result $result The result object returned by the Service.
     * @return bool
     */
    public function response(?Result $result = null): bool
    {
        $response = $this->service->getResponse();
        $data = $result->getData();
        $payload = $result->getPayload();
        if (is_array($data) && is_array($payload)) {
            $data = Hash::merge($data, $payload);
        }
        $this->service->setResponse($response->withStringBody($this->encode($data))->withStatus($result->getCode())
            ->withType('application/json'));

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
        $response = $this->service->getResponse();
        $code = $exception->getCode();
        if ($code === 0) {
            $code = $this->service->getResponse()->getStatusCode();
        }
        $data = [
            'error' => [
                'code' => $code,
                'message' => $this->buildMessage($exception),
            ],
        ];
        if (Configure::read('debug')) {
            $data['error']['trace'] = $this->stackTrace($exception);
        }
        if ($exception instanceof ValidationException) {
            $data['error']['validation'] = $exception->getValidationErrors();
        }
        $this->service->setResponse($response->withStringBody($this->encode($data))->withType('application/json'));
    }

    /**
     * Encoded object as json. In debug mode used pretty printed objects.
     *
     * @param mixed $data Encoded data.
     * @return string
     */
    protected function encode($data): string
    {
        $format = Configure::read('debug') ? JSON_PRETTY_PRINT : 0;

        return json_encode($data, $format);
    }
}
