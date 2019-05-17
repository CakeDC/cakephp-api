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
    public function getParams(): array
    {
        $request = $this->_service->getRequest();
        if ($request == null) {
            stackTrace();
        }
        if ($request->is(['post', 'put'])) {
            return (array)$request->getData();
        }

        return (array)$request->getQuery();
    }

    /**
     * Processes the HTTP request.
     *
     * @return bool
     */
    public function request(): bool
    {
        return true;
    }
}
