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
 * Class MethodNotAllowedException
 * Used to configure an exception for a service error.
 *
 * @package CakeDC\Api\Exception
 */
class MethodNotAllowedException extends \Cake\Core\Exception\CakeException
{
    /**
     * MethodNotAllowedException constructor.
     *
     * @param string $message the string of the error message
     * @param int $code The code of the error
     * @param \Exception|null $previous the previous exception.
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
     * @param string $file file name
     * @return void
     */
    public function setFile(string $file = ''): void
    {
        $this->file = $file;
    }

    /**
     * Line setter
     *
     * @param int $line set the line of the code
     * @return void
     */
    public function setLine(int $line = 0): void
    {
        $this->line = $line;
    }
}
