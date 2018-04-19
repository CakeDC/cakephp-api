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

namespace CakeDC\Api\Service\RequestParser;

use CakeDC\Api\Service\Service;

/**
 * Base class for a Service content negotiation Adapter.
 */
abstract class BaseParser
{

    /**
     * Reference to the Service.
     *
     * @var \CakeDC\Api\Service\Service
     */
    protected $_service = null;

    /**
     * Constructor
     *
     * @param Service $service The Service instantiating the Adapter.
     */
    public function __construct(Service $service)
    {
        $this->_service = $service;
    }

    /**
     * Resolves the request params as a key => value array.
     *
     * @return array
     */
    abstract public function params();

    /**
     * Processes the HTTP request.
     *
     * @return bool
     */
    abstract public function request();
}
