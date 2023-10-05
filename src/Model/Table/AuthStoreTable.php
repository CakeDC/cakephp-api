<?php
declare(strict_types=1);

namespace CakeDC\Api\Model\Table;

use Cake\Database\Schema\TableSchemaInterface;
use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * AuthStore Model
 *
 * @method \CakeDC\Api\Model\Entity\AuthStore newEmptyEntity()
 * @method \CakeDC\Api\Model\Entity\AuthStore newEntity(array $data, array $options = [])
 * @method \CakeDC\Api\Model\Entity\AuthStore[] newEntities(array $data, array $options = [])
 * @method \CakeDC\Api\Model\Entity\AuthStore get($primaryKey, $options = [])
 * @method \CakeDC\Api\Model\Entity\AuthStore findOrCreate($search, ?callable $callback = null, $options = [])
 * @method \CakeDC\Api\Model\Entity\AuthStore patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \CakeDC\Api\Model\Entity\AuthStore[] patchEntities(iterable $entities, array $data, array $options = [])
 * @method \CakeDC\Api\Model\Entity\AuthStore|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \CakeDC\Api\Model\Entity\AuthStore saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \CakeDC\Api\Model\Entity\AuthStore[]|\Cake\Datasource\ResultSetInterface|false saveMany(iterable $entities, $options = [])
 * @method \CakeDC\Api\Model\Entity\AuthStore[]|\Cake\Datasource\ResultSetInterface saveManyOrFail(iterable $entities, $options = [])
 * @method \CakeDC\Api\Model\Entity\AuthStore[]|\Cake\Datasource\ResultSetInterface|false deleteMany(iterable $entities, $options = [])
 * @method \CakeDC\Api\Model\Entity\AuthStore[]|\Cake\Datasource\ResultSetInterface deleteManyOrFail(iterable $entities, $options = [])
 *
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class AuthStoreTable extends Table
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

        $this->setTable('auth_store');
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
            ->scalar('store')
            ->allowEmptyString('store');

        return $validator;
    }


    /**
     * Field additional_data is json
     *
     * @param \Cake\Database\Schema\TableSchemaInterface $schema The table definition fetched from database.
     * @return \Cake\Database\Schema\TableSchemaInterface the altered schema
     */
    protected function _initializeSchema(TableSchemaInterface $schema): TableSchemaInterface
    {
        $schema->setColumnType('store', 'json');

        return parent::_initializeSchema($schema);
    }
}
