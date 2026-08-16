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

use Cake\ORM\TableRegistry;
use CakeDC\Api\Model\Entity\AuthStore;
use CakeDC\Api\TestSuite\TestCase;

/**
 * Unit tests for the AuthStore entity and table.
 */
class AuthStoreTest extends TestCase
{
    protected array $fixtures = [
        'plugin.CakeDC/Api.AuthStore',
    ];

    public function testEntityMassAssignment(): void
    {
        $table = TableRegistry::getTableLocator()->get('CakeDC/Api.AuthStore');
        $entity = $table->newEntity([
            'id' => 'some-uuid',
            'store' => 'scalar-store-value',
        ]);
        $this->assertSame('some-uuid', $entity->get('id'));
        $this->assertSame('scalar-store-value', $entity->get('store'));
    }

    public function testArrayStoreRejectedByScalarRule(): void
    {
        // NOTE: validationDefault uses scalar('store') so array payloads are moved to invalid
        $table = TableRegistry::getTableLocator()->get('CakeDC/Api.AuthStore');
        $entity = $table->newEntity(['store' => ['key' => 'value']]);
        $this->assertNull($entity->get('store'));
        $this->assertNotEmpty($entity->getInvalid());
    }

    public function testEntityAccessibleFields(): void
    {
        $entity = new AuthStore(['id' => 'some-uuid', 'store' => ['a' => 1]]);
        $this->assertSame('some-uuid', $entity->get('id'));
        $this->assertSame(['a' => 1], $entity->get('store'));
    }

    public function testTableConfig(): void
    {
        $table = TableRegistry::getTableLocator()->get('CakeDC/Api.AuthStore');
        $this->assertSame('auth_store', $table->getTable());
        $this->assertSame('id', $table->getPrimaryKey());
        $this->assertTrue($table->hasBehavior('Timestamp'));
    }

    public function testStoreColumnIsJson(): void
    {
        $table = TableRegistry::getTableLocator()->get('CakeDC/Api.AuthStore');
        $schema = $table->getSchema();
        $this->assertSame('json', $schema->getColumnType('store'));
    }

    public function testSaveAndFindRoundtrip(): void
    {
        $table = TableRegistry::getTableLocator()->get('CakeDC/Api.AuthStore');
        $entity = $table->newEntity(['store' => 'store-data']);
        $saved = $table->save($entity);
        $this->assertNotFalse($saved);
        $found = $table->get($saved->get('id'));
        $this->assertSame('store-data', $found->get('store'));
    }
}
