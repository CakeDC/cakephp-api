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
 * Declares a service action route on the action class itself.
 *
 * Equivalent to one `Service::mapAction()` registration. The action class is
 * discovered by the `ApiActions` attribute on the service.
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
readonly class ApiRoute
{
    /**
     * @param string $action Action name (route key).
     * @param string $path Route path, relative to the service resource (or `ApiScope` prefix). Supports `{placeholder}` params.
     * @param string|array<string> $method HTTP method(s).
     * @param bool $mapCors Register an OPTIONS route for CORS preflight.
     * @param string|null $name Optional route name.
     * @param array<string, string> $patterns Regex patterns for route placeholders.
     * @param array<string, mixed> $defaults Additional route defaults.
     * @param array<string>|null $pass Placeholder names passed to the action.
     */
    public function __construct(
        public string $action,
        public string $path,
        public string|array $method = 'GET',
        public bool $mapCors = false,
        public ?string $name = null,
        public array $patterns = [],
        public array $defaults = [],
        public ?array $pass = null,
    ) {
    }
}
