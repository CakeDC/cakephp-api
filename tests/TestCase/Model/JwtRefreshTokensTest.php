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
use CakeDC\Api\Model\Entity\JwtRefreshToken;
use CakeDC\Api\TestSuite\TestCase;

/**
 * Unit tests for the JwtRefreshToken entity and table.
 */
class JwtRefreshTokensTest extends TestCase
{
    protected array $fixtures = [
        'plugin.CakeDC/Api.JwtRefreshTokens',
    ];

    public function testEntityIdNotMassAssignable(): void
    {
        $table = TableRegistry::getTableLocator()->get('CakeDC/Api.JwtRefreshTokens');
        $entity = $table->newEntity([
            'id' => 'some-uuid',
            'model' => 'Users',
            'foreign_key' => '1',
            'token' => 'token-value',
            'expired' => 123456,
        ]);
        $this->assertNull($entity->get('id'));
        $this->assertSame('Users', $entity->get('model'));
        $this->assertSame('token-value', $entity->get('token'));
        $this->assertSame(123456, $entity->get('expired'));
    }

    public function testEntityTokenHidden(): void
    {
        $entity = new JwtRefreshToken(['token' => 'secret-token']);
        $this->assertContains('token', $entity->getHidden());
        $this->assertArrayNotHasKey('token', $entity->toArray());
    }

    public function testTableConfig(): void
    {
        $table = TableRegistry::getTableLocator()->get('CakeDC/Api.JwtRefreshTokens');
        $this->assertSame('jwt_refresh_tokens', $table->getTable());
        $this->assertSame('id', $table->getPrimaryKey());
        $this->assertTrue($table->hasBehavior('Timestamp'));
    }

    public function testSaveAndFind(): void
    {
        $table = TableRegistry::getTableLocator()->get('CakeDC/Api.JwtRefreshTokens');
        $entity = $table->newEntity([
            'model' => 'Users',
            'foreign_key' => '1',
            'token' => 'token-value',
            'expired' => 123456,
        ]);
        $saved = $table->save($entity);
        $this->assertNotFalse($saved);
        $found = $table->find()
            ->where(['model' => 'Users', 'foreign_key' => '1'])
            ->first();
        $this->assertNotNull($found);
        $this->assertSame('token-value', $found->get('token'));
    }

    public function testValidation(): void
    {
        $table = TableRegistry::getTableLocator()->get('CakeDC/Api.JwtRefreshTokens');
        $entity = $table->newEntity(['token' => 'x']);
        $this->assertArrayHasKey('model', $entity->getErrors());
        $this->assertArrayHasKey('foreign_key', $entity->getErrors());
    }
}
