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

namespace CakeDC\Api\Service\Action;

use Exception;

/**
 * Class Result
 *
 * @package CakeDC\Api\Service\Action
 */
class Result
{

    /**
     * Response code
     *
     * @var int
     */
    protected $_code = 200;

    /**
     * Response data
     *
     * @var array|mixed
     */
    protected $_data = null;

    /**
     * Response payload
     *
     * @var array
     */
    protected $_payload = [];

    /**
     * Exception structure
     *
     * @var Exception
     */
    protected $_exception = null;

    /**
     * Result constructor.
     *
     * @param array $data data to be delivered for the api
     * @param int $code code of the api request
     */
    public function __construct($data = null, $code = null)
    {
        if ($data !== null) {
            $this->setData($data);
        }
        if ($code !== null) {
            $this->setCode($code);
        }
    }

    /**
     * Gets a result data.
     *
     * @return array|mixed
     */
    public function getData()
    {
        return $this->_data;
    }

    /**
     * Sets a result data.
     *
     * @param array|mixed $value data to be delivered for the api
     * @return $this
     */
    public function setData($value)
    {
        $this->_data = $value;

        return $this;
    }

    /**
     * Get and set result data.
     *
     * @param array|mixed $value data to be delivered for the api
     * @deprecated 3.6.0 Use setData()/getData() instead.
     * @return array
     */
    public function data($value = null)
    {
        deprecationWarning(
            'Result::data() is deprecated. ' .
            'Use Result::setData()/getData() instead.'
        );

        if ($value !== null) {
            return $this->setData($value);
        }

        return $this->getData();
    }

    /**
     * Gets a result code.
     *
     * @return int
     */
    public function getCode()
    {
        return $this->_code;
    }

    /**
     * Sets a result code.
     *
     * @param int $value code to be delivered for the api
     * @return $this
     */
    public function setCode($value)
    {
        $this->_code = $value;

        return $this;
    }

    /**
     * Code api method.
     *
     * @param int $value code of the api request
     * @deprecated 3.6.0 Use setCode()/getCode() instead.
     * @return int
     */
    public function code($value = null)
    {
        deprecationWarning(
            'Result::code() is deprecated. ' .
            'Use Result::setCode()/getCode() instead.'
        );

        if ($value !== null) {
            return $this->setCode($value);
        }

        return $this->getCode();
    }

    /**
     * Gets a result exception.
     *
     * @return Exception
     */
    public function getException()
    {
        return $this->_exception;
    }

    /**
     * Sets a result exception.
     *
     * @param Exception $value exception to be delivered for the api
     * @return $this
     */
    public function setException($value)
    {
        $this->_exception = $value;

        return $this;
    }

    /**
     * Exception api method.
     *
     * @param Exception $value exception of the api request
     * @deprecated 3.6.0 Use setException()/getException() instead.
     * @return Exception
     */
    public function exception($value = null)
    {
        deprecationWarning(
            'Result::exception() is deprecated. ' .
            'Use Result::setException()/getException() instead.'
        );

        if ($value !== null) {
            return $this->setException($value);
        }

        return $this->getException();
    }

    /**
     * Appends value to Payload.
     *
     * @param string $key the key to be used in the payload
     * @param mixed $value value to be used as payload
     * @return void
     */
    public function appendPayload($key, $value)
    {
        $this->_payload[$key] = $value;
    }

    /**
     * Gets a result payload.
     *
     * @return array|mixed Payload
     */
    public function getPayload($key = null)
    {
        if ($key === null) {
            return $this->_payload;
        }

        if (isset($this->_payload[$key])) {
            return $this->_payload[$key];
        }

        return null;
    }

    /**
     * Sets a result payload.
     *
     * @param mixed $value payload to be delivered for the api
     * @return $this
     */
    public function setPayload($value)
    {
        $this->_payload = $value;

        return $this;
    }

    /**
     * Payload api method.
     *
     * @param string $value payload of the api request
     * @deprecated 3.6.0 Use getPayload() instead.
     * @return mixed
     */
    public function payload($key = null)
    {
        deprecationWarning(
            'Result::payload() is deprecated. ' .
            'Use Result::getPayload() instead.'
        );

        return $this->getPayload($key);
    }

    /**
     * To array transformation.
     *
     * @return array
     */
    public function toArray()
    {
        $info = [
            'code' => $this->_code,
            'data' => $this->_data,
            'payload' => $this->_payload,
        ];
        if ($this->_exception !== null) {
            $info['exception'] = $this->_exception->getMessage();
            $info['exceptionStack'] = $this->_exception->getTraceAsString();
        }

        return $info;
    }

    /**
     * Returns an array that can be used to describe the internal state of this
     * object.
     *
     * @return array
     */
    public function __debugInfo()
    {
        return $this->toArray();
    }
}
