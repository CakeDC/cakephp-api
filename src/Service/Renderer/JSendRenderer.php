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
use stdClass;

/**
 * Class JSendRenderer
 * JSend content negotiation Renderer.
 *
 * @package CakeDC\Api\Service\Renderer
 */
class JSendRenderer extends BaseRenderer
{
    /**
     * Success status.
     */
    public const STATUS_SUCCESS = 'success';

    /**
     * Fail status.
     */
    public const STATUS_FAIL = 'fail';

    /**
     * Error status.
     */
    public const STATUS_ERROR = 'error';

    /**
     * Response status.
     */
    public $status = self::STATUS_SUCCESS;

    /**
     * HTTP error code.
     */
    public $errorCode = 200;

    /**
     * Confirms if the specified content type is acceptable for the response.
     *
     * @return bool
     */
    public function accept(): bool
    {
        $request = $this->_service->getRequest();
        $json = $request->accepts('application/json') || $request->accepts('text/json');
        $js = $request->accepts('text/javascript');

        return $json || $js;
    }

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
        $payload = $result->getPayload();
        $return = [
            'data' => $data,
        ];
        if (is_array($payload)) {
            $return = Hash::merge($return, $payload);
        }
        $this->_mapStatus($result);

        $response = $response->withStringBody($this->_format($this->status, $return))
            ->withStatus($result->getCode())
            ->withType('application/json');
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
        if ($exception instanceof ValidationException) {
            $data = $exception->getValidationErrors();
        } else {
            $data = null;
        }
        $message = $this->_buildMessage($exception);
        $trace = $this->_stackTrace($exception);
        $response = $response->withStringBody($this->_error($message, $exception->getCode(), $data, $trace))
            ->withStatus((int)$this->errorCode)
            ->withType('application/json');
        $this->_service->setResponse($response);
    }

    /**
     * Formats a response to JSend specification.
     *
     * @param string $status The status of the response.
     * @param array $response The response properties.
     * @return string
     */
    protected function _format(string $status, array $response = []): string
    {
        $object = new stdClass();
        $object->status = $status;
        foreach ($response as $param => $value) {
            $object->{$param} = $value;
        }
        $format = Configure::read('debug') ? JSON_PRETTY_PRINT : 0;

        return json_encode($object, $format);
    }

    /**
     * Creates a successful response.
     *
     * @param array $data The response data object.
     * @return string
     */
    protected function _success(?array $data = null): string
    {
        return $this->_format(self::STATUS_SUCCESS, ['data' => $data]);
    }

    /**
     * Creates a failure response.
     *
     * @param array $data The response data object.
     * @return string
     */
    protected function _fail(?array $data = null): string
    {
        return $this->_format(self::STATUS_FAIL, ['data' => $data]);
    }

// phpcs:disable
    /**
     * Creates an error response.
     *
     * @param string $message The error message.
     * @param mixed $code The error code.
     * @param array $data The response data object.
     * @param array $trace The exception trace
     * @return string
     */
    protected function _error(string $message = 'Unknown error', $code = 0, ?array $data = null, ?array $trace = null): string
    {
// phpcs:enable
        $response = [
            'message' => $message,
            'code' => $code,
            'data' => $data,
        ];
        if (Configure::read('debug') > 0) {
            $response['trace'] = $trace;
        }

        return $this->_format(self::STATUS_ERROR, $response);
    }

    /**
     * Update status based on result code
     *
     * @param \CakeDC\Api\Service\Action\Result $result A result object instance.
     * @return void
     */
    protected function _mapStatus(Result $result): void
    {
        $code = (int)$result->getCode();
        if ($code == 0 || $code >= 200 && $code <= 399) {
            $this->status = self::STATUS_SUCCESS;
        } else {
            $this->status = self::STATUS_ERROR;
        }
    }
}
