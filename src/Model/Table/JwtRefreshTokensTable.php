<?php
declare(strict_types=1);

/**
 * Copyright 2018 - 2020, Cake Development Corporation (http://cakedc.com)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright 2018 - 2020, Cake Development Corporation (http://cakedc.com)
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

namespace CakeDC\Api\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * JwtRefreshTokens Model
 *
 * @method \CakeDC\Api\Model\Entity\JwtRefreshToken get($primaryKey, $options = [])
 * @method \CakeDC\Api\Model\Entity\JwtRefreshToken newEntity($data = null, array $options = [])
 * @method \CakeDC\Api\Model\Entity\JwtRefreshToken[] newEntities(array $data, array $options = [])
 * @method \CakeDC\Api\Model\Entity\JwtRefreshToken|bool save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \CakeDC\Api\Model\Entity\JwtRefreshToken patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \CakeDC\Api\Model\Entity\JwtRefreshToken[] patchEntities($entities, array $data, array $options = [])
 * @method \CakeDC\Api\Model\Entity\JwtRefreshToken findOrCreate($search, callable $callback = null, $options = [])
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class JwtRefreshTokensTable extends Table
{
    /**
     * Initialize method
     *
     * @param array $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('jwt_refresh_tokens');
        $this->setDisplayField('id');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');
    }

    /**
     * Default validation rules.
     *
     * @param \Cake\Validation\Validator $validator Validator instance.
     * @return \Cake\Validation\Validator
     */
    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->uuid('id')
            ->allowEmptyString('id', 'create');

        $validator
            ->requirePresence('model', 'create')
            ->notEmptyString('model');

        $validator
            ->requirePresence('foreign_key', 'create')
            ->notEmptyString('foreign_key');

        $validator
            ->allowEmptyString('token');

        $validator
            ->integer('expired')
            ->requirePresence('expired', 'create');

        return $validator;
    }
}
