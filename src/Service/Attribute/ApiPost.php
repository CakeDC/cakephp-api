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
 * POST route shortcut.
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
readonly class ApiPost extends ApiRoute
{
    /**
     * Route shortcut constructor.
     *
     * @param string $action Action name.
     * @param string $path Route path.
     * @param bool $mapCors Register an OPTIONS route for CORS preflight.
     * @param string|null $name Route name.
     * @param array<string, string> $patterns Route patterns.
     * @param array<string, mixed> $defaults Route defaults.
     * @param array<string>|null $pass Passed placeholders.
     */
    public function __construct(
        string $action,
        string $path,
        bool $mapCors = false,
        ?string $name = null,
        array $patterns = [],
        array $defaults = [],
        ?array $pass = null,
    ) {
        parent::__construct($action, $path, 'POST', $mapCors, $name, $patterns, $defaults, $pass);
    }
}
