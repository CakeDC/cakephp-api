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

namespace CakeDC\Api\Service\RequestParser;

/**
 * Parse form based data representation.
 */
class FormParser extends BaseParser
{

    /**
     * Resolves the request params as a key => value array.
     *
     * @return array
     */
    public function params()
    {
        $request = $this->_service->request();
        if ($request->is(['post', 'put'])) {
            return $request->data;
        }

        return $request->query;
    }

    /**
     * Processes the HTTP request.
     *
     * @return bool
     */
    public function request()
    {
        return true;
    }
}
