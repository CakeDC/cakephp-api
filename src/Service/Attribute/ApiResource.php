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
 * Declares REST resource routes for a service, equivalent to passing options to
 * `$routes->resources()` when the service routes are loaded.
 */
#[Attribute(Attribute::TARGET_CLASS)]
readonly class ApiResource
{
    /**
     * @param string|null $path Override the resource URL path.
     * @param array<string> $only Limit which REST actions are generated (index/view/add/edit/delete).
     * @param array<string, string> $actions Map REST actions to custom action classes.
     * @param array<string, array> $map Additional non-standard resource routes.
     * @param string $id Regex pattern for the resource identifier.
     */
    public function __construct(
        public ?string $path = null,
        public array $only = [],
        public array $actions = [],
        public array $map = [],
        public string $id = '[0-9]+',
    ) {
    }
}
