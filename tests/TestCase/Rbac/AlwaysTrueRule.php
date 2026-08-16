<?php
declare(strict_types=1);

/**
 * Copyright 2016 - 2026, Cake Development Corporation (http://cakedc.com)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright 2016 - 2026, Cake Development Corporation (http://cakedc.com)
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

namespace CakeDC\Api\Test\TestCase\Rbac;

use CakeDC\Auth\Rbac\Rules\Rule;
use Psr\Http\Message\ServerRequestInterface;

class AlwaysTrueRule implements Rule
{
    public function allowed(array|\ArrayAccess $user, string $role, ServerRequestInterface $request): bool
    {
        return true;
    }
}
