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

namespace CakeDC\Api\Utility;

use Cake\Http\ServerRequest;

/**
 * RequestParser class.
 */
class RequestParser
{
    /**
     * Get the domain from the request.
     *
     * @param \Cake\Http\ServerRequest $request The request object.
     * @param bool $replace Whether to replace the domain.
     * @return string
     */
    public static function getDomain(ServerRequest $request, $replace = true): string
    {
        $domain = null;
        if (isset($_SERVER['HTTP_REFERER']) && $_SERVER['HTTP_REFERER']) {
            $domain = parse_url($_SERVER['HTTP_REFERER']);
        }
        $host = $domain !== null && $domain['host'] ? $domain['host'] : $request->domain();

        if ($replace) {
            return str_replace('.', '$', $host);
        }

        return $host;
    }
}
