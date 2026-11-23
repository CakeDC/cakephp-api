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

namespace CakeDC\Api\Test\TestCase\Service\Action\Extension;

use Cake\ORM\TableRegistry;
use CakeDC\Api\Service\Action\CrudAction;
use CakeDC\Api\TestSuite\TestCase;

class SortableTraitTest extends TestCase
{
    protected array $fixtures = [
        'plugin.CakeDC/Api.Users',
    ];

    public function testWithoutTableEmptySortableFieldsRejectsEverything(): void
    {
        // an action that is not a CrudAction has no table columns to fall back to
        $harness = new SortableHarness();
        $this->assertNull($harness->sortField(null, 'first_name'));
        $this->assertNull($harness->sortField(null, 'Authors.id'));
    }

    public function testWithoutTableOnlyListedFieldsAreAccepted(): void
    {
        $harness = new SortableHarness(['sortableFields' => ['first_name', 'Authors.id']]);
        $this->assertSame('first_name', $harness->sortField(null, 'first_name'));
        $this->assertSame('Authors.id', $harness->sortField(null, 'Authors.id'));
        $this->assertNull($harness->sortField(null, 'last_name'));
        $this->assertNull($harness->sortField(null, '(CASE WHEN 1=1 THEN first_name END)'));
    }

    public function testNonStringFieldIsRejected(): void
    {
        // `?sort[]=x` gives an array, and a JSON list or numeric key gives an int
        $harness = new SortableHarness(['sortableFields' => ['first_name', '0']]);
        $this->assertNull($harness->sortField(null, ['first_name']));
        $this->assertNull($harness->sortField(null, 0));
        $this->assertNull($harness->sortField(null, null));
    }

    /**
     * A CrudAction whose table is the users plugin table, whose entity hides
     * the password and token columns.
     *
     * @return \CakeDC\Api\Service\Action\CrudAction
     */
    protected function usersAction(): CrudAction
    {
        $action = $this->createStub(CrudAction::class);
        $action->method('getTable')->willReturn(TableRegistry::getTableLocator()->get('CakeDC/Users.Users'));

        return $action;
    }

    public function testHiddenColumnIsNotSortableByDefault(): void
    {
        $harness = new SortableHarness();
        $this->assertNull($harness->sortField($this->usersAction(), 'password'));
        $this->assertNull($harness->sortField($this->usersAction(), 'Users.password'));
        $this->assertNull($harness->sortField($this->usersAction(), 'token'));
        $this->assertSame('Users.username', $harness->sortField($this->usersAction(), 'username'));
    }

    public function testHiddenColumnCanBeAllowedExplicitly(): void
    {
        $harness = new SortableHarness(['sortableFields' => ['password']]);
        $this->assertSame('Users.password', $harness->sortField($this->usersAction(), 'password'));
    }
}
