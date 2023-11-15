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

class RequestParser
{
    public static function getDomain(ServerRequest $request, $replace = true)
    {
        $domain = null;
        if (isset($_SERVER['HTTP_REFERER']) && $_SERVER['HTTP_REFERER']) {
            $domain = parse_url($_SERVER['HTTP_REFERER']);
        }
        if ($domain !==null && $domain['host']) {
            $host = $domain['host'];
        } else {
            $host = $this->request->domain();
        }

        if ($replace) {
            return str_replace('.', '$', $host);
        } else {
            return $host;
        }
    }
}
