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
     * @var \Exception|null
     */
    protected $_exception = null;

    /**
     * Result constructor.
     *
     * @param array|null $data data to be delivered for the api
     * @param int $code code of the api request
     */
    public function __construct(?array $data = null, $code = null)
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
     * @return self
     */
    public function setData($value)
    {
        $this->_data = $value;

        return $this;
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
     * @return self
     */
    public function setCode($value)
    {
        $this->_code = $value;

        return $this;
    }

    /**
     * Gets a result exception.
     *
     * @return \Exception|null
     */
    public function getException(): ?Exception
    {
        return $this->_exception;
    }

    /**
     * Sets a result exception.
     *
     * @param \Exception $value exception to be delivered for the api
     * @return self
     */
    public function setException($value)
    {
        $this->_exception = $value;

        return $this;
    }

    /**
     * Appends value to Payload.
     *
     * @param string $key the key to be used in the payload
     * @param mixed $value value to be used as payload
     * @return void
     */
    public function appendPayload(string $key, $value): void
    {
        $this->_payload[$key] = $value;
    }

    /**
     * Gets a result payload.
     *
     * @param string $key Payload key.
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
