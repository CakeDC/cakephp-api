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
 * Declares shared path, defaults, and patterns for all attribute routes of a service.
 *
 * Repeatable and stacked across class inheritance: parent scope values are applied
 * first, then child values.
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
readonly class ApiScope
{
    /**
     * @param string $path Path prefix prepended to every route path of the service.
     * @param array<string, mixed> $defaults Default route values merged into every route.
     * @param array<string, string> $patterns Shared regex patterns for placeholders.
     */
    public function __construct(
        public string $path = '',
        public array $defaults = [],
        public array $patterns = [],
    ) {
    }
}
