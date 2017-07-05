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

use CakeDC\Api\Service\Action\Result;
use CakeDC\Api\Service\Renderer\PayloadRenderer\PayloadRendererCollection;
use CakeDC\Api\Service\Renderer\PayloadRenderer\PayloadRendererInterface;
use CakeDC\Api\Service\Service;
use Cake\Core\Configure;
use Cake\Core\InstanceConfigTrait;
use Exception;

/**
 * Base class for a Service content negotiation Renderer.
 */
abstract class BaseRenderer
{

    use InstanceConfigTrait;

    /**
     * Default config
     *
     * These are merged with user-provided config when the object is used.
     *
     * @var array
     */
    protected $_defaultConfig = [
        'payloadRenderers' => [
            'CakeDC/Api.Pagination',
//            'CakeDC/Api.Merge',
        ]
    ];

    /**
     * Reference to the Service.
     *
     * @var \CakeDC\Api\Service\Service
     */
    protected $_service = null;

    /**
     * @var PayloadRendererCollection
     */
    protected $_payloadRenderers;

    /**
     * Constructor
     *
     * @param Service $service The Service instantiating the Renderer.
     */
    public function __construct(Service $service)
    {
        $this->_service = $service;
    }

    /**
     * Access the payload renderers collection
     *
     * @return \CakeDC\Api\Service\Renderer\PayloadRenderer\PayloadRendererCollection
     */
    public function payloadRenderers()
    {
        if (!$this->_payloadRenderers) {
            $this->_payloadRenderers = new PayloadRendererCollection($this->getConfig('payloadRenderers') ?: []);
        }

        return $this->_payloadRenderers;
    }

    /**
     * Updates response and result data based on payload.
     *
     * @param array $payload Payload data object.
     * @param mixed $data An api return data.
     * @return mixed
     */
    public function applyPayload(array $payload, $data)
    {
        if ($payload === null || !is_array($payload)) {
            return $data;
        }

        $response = $this->_service->getResponse();
        foreach ($this->payloadRenderers() as $payloadRenderer) {
            $response = $payloadRenderer->applyToResponse($response, $payload);
            $data = $payloadRenderer->applyToResultData($data, $payload);
        }

        $this->_service->setResponse($response);

        return $data;
    }

    /**
     * Confirms if the specified content type is acceptable for the response.
     *
     * @return bool
     */
    public function accept()
    {
        return true;
    }

    /**
     * Builds the HTTP response.
     *
     * @param Result $result The result object returned by the Service.
     * @return bool
     */
    abstract public function response(Result $result = null);

    /**
     * Processes an exception thrown while processing the request.
     *
     * @param Exception $exception The exception object.
     * @return void
     */
    abstract public function error(Exception $exception);

    /**
     * Format error message.
     *
     * @param Exception $exception An Exception instance.
     * @return string
     */
    protected function _buildMessage(Exception $exception)
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
     * @param Exception $exception An Exception instance.
     * @return array
     */
    protected function _stackTrace(Exception $exception)
    {
        if (Configure::read('debug') == 0) {
            return null;
        }
        $trace = $exception->getTrace();
        $count = count($trace);
        for ($i = 0; $i < $count; $i++) {
            foreach ($trace[$i] as $key => $value) {
                if ($key === 'object' || $key === 'type' || $key === 'args') {
                    unset($trace[$i][$key]);
                }
            }
        }

        return $trace;
    }
}
