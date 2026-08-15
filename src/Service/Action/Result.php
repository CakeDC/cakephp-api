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
     */
    protected int $code = 200;

    /**
     * Response data
     *
     * @var array|mixed
     */
    protected $data;

    /**
     * Response payload
     */
    protected array $payload = [];

    /**
     * Exception structure
     */
    protected ?\Exception $exception = null;

    /**
     * Result constructor.
     *
     * @param array|null $data data to be delivered for the api
     * @param int $code code of the api request
     */
    public function __construct(?array $data = null, ?int $code = null)
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
    public function getData(): mixed
    {
        return $this->data;
    }

    /**
     * Sets a result data.
     *
     * @param array|mixed $value data to be delivered for the api
     * @return self
     */
    public function setData($value): self
    {
        $this->data = $value;

        return $this;
    }

    /**
     * Gets a result code.
     *
     * @return int
     */
    public function getCode(): int
    {
        return $this->code;
    }

    /**
     * Sets a result code.
     *
     * @param int $value code to be delivered for the api
     * @return self
     */
    public function setCode(int $value): self
    {
        $this->code = $value;

        return $this;
    }

    /**
     * Gets a result exception.
     *
     * @return \Exception|null
     */
    public function getException(): ?Exception
    {
        return $this->exception;
    }

    /**
     * Sets a result exception.
     *
     * @param \Exception $value exception to be delivered for the api
     * @return self
     */
    public function setException(\Exception $value): self
    {
        $this->exception = $value;

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
        $this->payload[$key] = $value;
    }

    /**
     * Gets a result payload.
     *
     * @param string $key Payload key.
     * @return array|null|mixed Payload
     */
    public function getPayload(?string $key = null): mixed
    {
        if ($key === null) {
            return $this->payload;
        }

        if (isset($this->payload[$key])) {
            return $this->payload[$key];
        }

        return null;
    }

    /**
     * Sets a result payload.
     *
     * @param array $value payload to be delivered for the api
     * @return $this
     */
    public function setPayload(array $value)
    {
        $this->payload = $value;

        return $this;
    }

    /**
     * To array transformation.
     *
     * @return array
     */
    public function toArray(): array
    {
        $info = [
            'code' => $this->code,
            'data' => $this->data,
            'payload' => $this->payload,
        ];
        if ($this->exception instanceof \Exception) {
            $info['exception'] = $this->exception->getMessage();
            $info['exceptionStack'] = $this->exception->getTraceAsString();
        }

        return $info;
    }

    /**
     * Returns an array that can be used to describe the internal state of this
     * object.
     *
     * @return array
     */
    public function __debugInfo(): array
    {
        return $this->toArray();
    }
}
