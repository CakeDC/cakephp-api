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

/**
 * Used to return validation errors.
 */
class ValidationException extends ServiceException
{
    protected $_defaultCode = 422;

    /**
     * Validation errors
     *
     * @var array
     */
    protected $_validationErrors = [];

    /**
     * Construct method, for fast instantiation
     *
     * @param string $message the string of the error message
     * @param int $code The code of the error
     * @param \Exception|null $previous the previous exception.
     * @param array $validationErrors the array with the validations
     */
    public function __construct(
        $message = 'Validation errors',
        $code = 0,
        $previous = null,
        $validationErrors = []
    ) {
        if ($code === 0) {
            $code = $this->_defaultCode;
        }
        $this->_validationErrors = $validationErrors;
        parent::__construct($message, $code, $previous);
    }

    /**
     * Sets validation errors
     *
     * @param array $validationErrors the array with the validations
     * @return void
     */
    public function setValidationErrors($validationErrors = [])
    {
        $this->_validationErrors = $validationErrors;
    }

    /**
     * Gets validation errors
     *
     * @return array
     */
    public function getValidationErrors()
    {
        return $this->_validationErrors;
    }
}
