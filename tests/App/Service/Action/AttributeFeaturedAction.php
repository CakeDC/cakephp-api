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
 * Returns published articles. The route is declared on the action class itself.
 */
#[ApiGet(action: 'featured', path: '/featured')]
class AttributeFeaturedAction extends CrudAction
{
    public function execute(): mixed
    {
        return $this->getTable()
            ->find()
            ->where(['published' => 'Y'])
            ->limit(2)
            ->all();
    }
}
