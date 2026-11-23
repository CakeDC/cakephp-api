<?php
declare(strict_types=1);

/**
 * Copyright 2016 - 2026, Cake Development Corporation (https://cakedc.com)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright 2016 - 2026, Cake Development Corporation (https://cakedc.com)
 * @license MIT License (https://www.opensource.org/licenses/mit-license.php)
 */

namespace CakeDC\Api\Test\TestCase\Rbac;

use CakeDC\Auth\Rbac\Rules\AbstractRule;
use Psr\Http\Message\ServerRequestInterface;

/**
 * A Rule whose result is its `allow` option, which json_encode() does not show.
 */
class OptionRule extends AbstractRule
{
    /**
     * @inheritDoc
     */
    public function allowed($user, $role, ServerRequestInterface $request)
    {
        return (bool)$this->getConfig('allow');
    }
}
