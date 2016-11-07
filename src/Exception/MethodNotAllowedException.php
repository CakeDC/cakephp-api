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
 * Class MethodNotAllowedException
 * Used to configure an exception for a service error.
 *
 * @package CakeDC\Api\Exception
 */
class MethodNotAllowedException extends Exception
{

    /**
     * MethodNotAllowedException constructor.
     *
     * @param string $message
     * @param int $code
     * @param Exception $previous
     */
    public function __construct($message = null, $code = 405, $previous = null)
    {
        if (empty($message)) {
            $message = 'Method not allowed';
        }
        parent::__construct($message, $code, $previous);
    }

    /**
     * File setter
     *
     * @param string $file
     * @return void
     */
    public function setFile($file = '')
    {
        $this->file = $file;
    }

    /**
     * Line setter
     *
     * @param int $line
     * @return void
     */
    public function setLine($line = 0)
    {
        $this->line = $line;
    }
}
