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

namespace CakeDC\Api\Test\App\Service;

use CakeDC\Api\Service\Attribute\ApiActions;
use CakeDC\Api\Service\Attribute\ApiResource;
use CakeDC\Api\Service\FallbackService;
use CakeDC\Api\Test\App\Service\Action\AttributeFeaturedAction;
use CakeDC\Api\Test\App\Service\Action\AttributesItemAction;

/**
 * Service using attribute-declared routes.
 *
 * Services own an actions map, not methods — `ApiActions` lists the action
 * classes, each carrying its route via an attribute. The `/api` base and version
 * prefixes are config-driven (Api.routeBase, Api.useVersioning).
 */
#[ApiResource(only: ['index', 'view', 'featured', 'item'])]
#[ApiActions([AttributeFeaturedAction::class, AttributesItemAction::class])]
class AttributesService extends FallbackService
{
}
