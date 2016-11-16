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

namespace CakeDC\Api\Exception;

use Cake\Core\Exception\Exception;

/**
 * Class UnauthenticatedException
 * Used to configure an exception for a service error.
 *
 * @package CakeDC\Api\Exception
 */
class UnauthenticatedException extends Exception
{

    /**
     * UnauthenticatedException constructor.
     *
     * @param string $message the string of the error message
     * @param int $code The code of the error
     * @param \Exception|null $previous the previous exception.
     */
    public function __construct($message = null, $code = 403, $previous = null)
    {
        if (empty($message)) {
            $message = 'Unauthenticated';
        }
        parent::__construct($message, $code, $previous);
    }

    /**
     * File setter
     *
     * @param string $file set file name
     * @return void
     */
    public function setFile($file = '')
    {
        $this->file = $file;
    }

    /**
     * Line setter
     *
     * @param int $line set the line of the code
     * @return void
     */
    public function setLine($line = 0)
    {
        $this->line = $line;
    }
}
