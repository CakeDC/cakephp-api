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

namespace CakeDC\Api\Exception;

use Throwable;

/**
 * Used to return validation errors.
 */
class ValidationException extends ServiceException
{
    protected int $defaultCode = 422;

    /**
     * Validation errors
     */
    protected array $validationErrors = [];

    /**
     * Construct method, for fast instantiation
     *
     * @param string $message the string of the error message
     * @param int $code The code of the error
     * @param \Exception|null $previous the previous exception.
     * @param array $validationErrors the array with the validations
     */
    public function __construct(
        array|string $message = 'Validation errors',
        ?int $code = 0,
        ?Throwable $previous = null,
        array $validationErrors = [],
    ) {
        if ($code === 0) {
            $code = $this->defaultCode;
        }
        $this->validationErrors = $validationErrors;
        parent::__construct($message, $code, $previous);
    }

    /**
     * Sets validation errors
     *
     * @param array $validationErrors the array with the validations
     * @return void
     */
    public function setValidationErrors(array $validationErrors = []): void
    {
        $this->validationErrors = $validationErrors;
    }

    /**
     * Gets validation errors
     *
     * @return array
     */
    public function getValidationErrors(): array
    {
        return $this->validationErrors;
    }
}
