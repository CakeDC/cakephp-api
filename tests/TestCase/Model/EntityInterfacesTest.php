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
use CakeDC\Api\TestSuite\TestCase;

/**
 * Contract checks for the entity description/labels interfaces.
 */
class EntityInterfacesTest extends TestCase
{
    public function testStubImplementsContracts(): void
    {
        $stub = new EntityDescriptionStub();
        $this->assertInstanceOf(EntityDescriptionInterface::class, $stub);
        $this->assertInstanceOf(EntityLabelsInterface::class, $stub);
        $this->assertSame(['id' => 'integer'], $stub->describeFields());
        $this->assertSame(['view' => 'GET'], $stub->describeAction());
        $this->assertSame(['id' => 'required'], $stub->describeParams());
        $this->assertSame(['id' => 'Identifier'], $stub->labels());
    }
}
