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
     * @var array
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
            $this->data($data);
        }
        if ($code !== null) {
            $this->code($code);
        }
    }

    /**
     * Data api method.
     *
     * @param array $value data to be delivered for the api
     * @return array
     */
    public function data($value = null)
    {
        if ($value === null) {
            return $this->_data;
        }
        $this->_data = $value;

        return $this->_data;
    }

    /**
     * Code api method.
     *
     * @param int $value code of the api request
     * @return int
     */
    public function code($value = null)
    {
        if ($value === null) {
            return $this->_code;
        }
        $this->_code = $value;

        return $this->_code;
    }

    /**
     * Exception api.
     *
     * @param Exception $value set the excepltion value
     * @return Exception
     */
    public function exception($value = null)
    {
        if ($value === null) {
            return $this->_exception;
        }
        $this->_exception = $value;

        return $this->_exception;
    }

    /**
     * Payload setter.
     *
     * @param string $key the key to be setted in the payload
     * @param mixed $value value to be setted as payload
     * @return void
     */
    public function setPayload($key, $value)
    {
        $this->_payload[$key] = $value;
    }

    /**
     * Payload api method
     *
     * @param string $key the key to get the payload
     * @return mixed
     */
    public function payload($key = null)
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
