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

namespace CakeDC\Api\Test\App\Service\Action;

use CakeDC\Api\Service\Action\CrudAction;
use CakeDC\Api\Service\Attribute\ApiGet;

/**
 * Returns the id received via the `{id}` route placeholder.
 */
#[ApiGet(action: 'item', path: '/item/{id}')]
class AttributesItemAction extends CrudAction
{
    public function execute(): mixed
    {
        return ['id' => $this->id];
    }
}
