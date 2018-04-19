<?php
/**
 * Copyright 2016 - 2018, Cake Development Corporation (http://cakedc.com)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright 2016 - 2018, Cake Development Corporation (http://cakedc.com)
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

namespace CakeDC\Api\Service\Renderer;

use CakeDC\Api\Exception\ValidationException;
use CakeDC\Api\Service\Action\Result;
use Cake\Collection\Collection;
use Cake\Core\Configure;
use Cake\Datasource\EntityInterface;
use Cake\Datasource\ResultSetInterface;
use Cake\I18n\FrozenTime;
use Cake\Utility\Xml;
use Exception;

/**
 * Class XmlRenderer
 * XML content negotiation Renderer.
 *
 * @package CakeDC\Api\Service\Renderer
 */
class XmlRenderer extends BaseRenderer
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
        $xml = $this->_format($result->getData());
        $this->_service->setResponse($response->withStringBody($this->_encode($xml))->withType('application/xml')
            ->withStatus($result->getCode()));

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
        $this->_service->setResponse($response->withStringBody($this->_encode($data))->withType('application/xml'));
    }

    /**
     * Formats a response as an XML structure.
     *
     * @param mixed $content The content to process.
     * @return string
     */
    protected function _format($content = null)
    {
        if (is_array($content) || $content instanceof Collection || $content instanceof ResultSetInterface) {
            $data = $this->_array($content);
        } elseif (is_object($content)) {
            $data = $this->_object($content);
        } else {
            $data = ['value' => $content];
        }

        return ['data' => $data];
    }

    /**
     * Formats an object as an XML node.
     *
     * @param object $data The object to process.
     * @return string
     */
    protected function _object($data)
    {
        $xml = [];
        if ($data instanceof EntityInterface) {
            $data = $data->toArray();
        }
        foreach ($data as $name => $value) {
            if (is_object($value) && $value instanceof \DateTime) {
                $property = [];
                $property['@'] = $value->format(\DateTime::ISO8601);
            } elseif (is_object($value) && $value instanceof FrozenTime) {
                $property = [];
                $property['@'] = $value->toIso8601String();
            } elseif (is_object($value)) {
                $property = $this->_object($value);
            } elseif (is_array($value)) {
                    $property = $this->_array($value);
            } else {
                $property = [];
                $property['@'] = $value !== null ? $value : '';
            }
            $property['@name'] = $name;
            $xml['property'][] = $property;
        }

        return ['object' => $xml];
    }

    /**
     * Formats an array as an XML node.
     *
     * @param array $data The array to process.
     * @return string
     */
    protected function _array($data)
    {
        $xml = [];
        $items = [];
        if ($data instanceof Collection) {
            $data = $data->toArray();
        }
        foreach ($data as $name => $value) {
            $item = [];
            $item['@key'] = $name;
            if (is_object($value)) {
                $item = $this->_object($value);
            } else {
                if (is_array($value)) {
                    $item = $this->_array($value);
                } else {
                    $item = [];
                    $item['@'] = $value !== null ? $value : '';
                }
            }
            $item['@key'] = $name;
            $items[] = $item;
        }
        $xml['array']['row'] = $items;

        return $xml;
    }

    /**
     * Encoded object as xml.
     *
     * @param mixed $data Encoded data.
     * @return string
     */
    protected function _encode($data)
    {
        $xmlObject = Xml::fromArray($data, ['format' => 'tags']);

        return $xmlObject->asXML();
    }
}
