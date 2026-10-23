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

namespace CakeDC\Api\Service\Attribute;

use Attribute;

/**
 * Declares the action classes owned by a service.
 *
 * Each action class carries its own route via `ApiRoute` / HTTP shortcut
 * attributes; this attribute tells the service which action classes to register
 * (equivalent to one `Service::mapAction()` call per action).
 */
#[Attribute(Attribute::TARGET_CLASS)]
readonly class ApiActions
{
    /**
     * @param string|array<string> $actions Action class names.
     */
    public function __construct(
        public string|array $actions,
    ) {
    }
}
