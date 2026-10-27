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

use ArrayAccess;
use CakeDC\Auth\Rbac\Rules\AbstractRule;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Rule that records the role it receives, to assert the role is passed as a string.
 */
class RoleCapturingRule extends AbstractRule
{
    /**
     * @var bool Whether the rule was invoked.
     */
    public bool $called = false;

    /**
     * @var string|null The role received by the rule.
     */
    public ?string $receivedRole = null;

    /**
     * @inheritDoc
     */
    public function allowed(array|ArrayAccess $user, string $role, ServerRequestInterface $request): bool
    {
        $this->called = true;
        $this->receivedRole = $role;

        return true;
    }
}
