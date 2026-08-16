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

namespace CakeDC\Api\Test\TestCase\Model;

use CakeDC\Api\Model\Entity\EntityDescriptionInterface;
use CakeDC\Api\Model\Entity\EntityLabelsInterface;

class EntityDescriptionStub implements EntityDescriptionInterface, EntityLabelsInterface
{
    public function describeFields(): array
    {
        return ['id' => 'integer'];
    }

    public function describeAction(): array
    {
        return ['view' => 'GET'];
    }

    public function describeParams(): array
    {
        return ['id' => 'required'];
    }

    public function labels(): array
    {
        return ['id' => 'Identifier'];
    }
}
