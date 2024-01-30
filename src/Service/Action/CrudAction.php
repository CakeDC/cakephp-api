<?php
declare(strict_types=1);

/**
 * Copyright 2016 - 2019, Cake Development Corporation (http://cakedc.com)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright 2016 - 2019, Cake Development Corporation (http://cakedc.com)
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

namespace CakeDC\Api\Service\Action;

use Cake\Datasource\EntityInterface;
use Cake\Datasource\Exception\InvalidPrimaryKeyException;
use Cake\Datasource\ResultSetInterface;
use Cake\ORM\Association;
use Cake\ORM\Query;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\Utility\Inflector;
use CakeDC\Api\Exception\ValidationException;
use CakeDC\Api\Service\Utility\ReverseRouting;

/**
 * Class CrudAction
 *
 * @package CakeDC\Api\Service\Action
 */
abstract class CrudAction extends Action
{
    protected ?\Cake\ORM\Table $_table = null;

    /**
     * Object Identifier
     *
     * @var mixed     */
    protected $_id = null;

    /**
     * Object Identifier name
     */
    protected string $_idName = 'id';

    /**
     * Crud service.
     *
     * @var \CakeDC\Api\Service\CrudService
     */
    protected $_service;

    /**
     * Parent Object Identifier
     *
     * Used for nested services
     *
     * @var mixed     */
    protected $_parentId = null;

    /**
     * Parent Object Identifier name
     */
    protected ?string $_parentIdName = null;

    /**
     * Api table finder method
     */
    protected ?string $_finder = null;

    /**
     * Action constructor.
     *
     * @param array $config Configuration options passed to the constructor
     * @throws \Exception
     */
    public function __construct(array $config = [])
    {
        if (!empty($config['service'])) {
            $this->setService($config['service']);
        }
        $tableName = empty($config['table']) ? $this->getService()->getTable() : $config['table'];
        if ($tableName instanceof Table) {
            $this->setTable($tableName);
        } else {
            $table = TableRegistry::getTableLocator()->get($tableName);
            $this->setTable($table);
        }
        if (!empty($config['id'])) {
            $this->_id = $config['id'];
        }
        if (!empty($config['idName'])) {
            $this->_idName = $config['idName'];
        }
        if (!empty($config['finder'])) {
            $this->_finder = $config['finder'];
        }
        if (!empty($config['parentId'])) {
            $this->_parentId = $config['parentId'];
        }
        if (!empty($config['parentIdName'])) {
            $this->_parentIdName = $config['parentIdName'];
        }
        if (!empty($config['table'])) {
            $this->setTable($config['table']);
        }
        parent::__construct($config);
    }

    /**
     * Gets a Table instance.
     *
     * @return \Cake\ORM\Table
     */
    public function getTable(): Table
    {
        return $this->_table;
    }

    /**
     * Sets the table instance.
     *
     * @param \Cake\ORM\Table $table A Table instance.
     * @return $this
     */
    public function setTable(Table $table)
    {
        $this->_table = $table;

        return $this;
    }

    /**
     * @return \CakeDC\Api\Service\CrudService
     */
    public function getService()
    {
        return $this->_service;
    }

    /**
     * Model id getter.
     *
     * @return mixed|string
     */
    public function getId()
    {
        return $this->_id;
    }

    /**
     * Model id field name getter.
     *
     * @return string
     */
    public function getIdName(): string
    {
        return $this->_idName;
    }

    /**
     * Parent id getter.
     *
     * @return mixed|string
     */
    public function getParentId()
    {
        return $this->_parentId;
    }

    /**
     * Parent model id field name getter.
     *
     * @return string
     */
    public function getParentIdName(): string
    {
        return $this->_parentIdName;
    }

    /**
     * Builds new entity instance.
     *
     * @return \Cake\Datasource\EntityInterface
     */
    protected function _newEntity(): EntityInterface
    {
        return $this->getTable()->newEntity([]);
    }

    /**
     * Patch entity.
     *
     * @param \Cake\Datasource\EntityInterface $entity An Entity instance.
     * @param array $data Entity data.
     * @param array $options Patch entity options.
     * @return \Cake\Datasource\EntityInterface
     */
    protected function _patchEntity(EntityInterface $entity, array $data, array $options = []): EntityInterface
    {
        $entity = $this->getTable()->patchEntity($entity, $data, $options);
        $event = $this->dispatchEvent('Action.Crud.onPatchEntity', ['entity' => $entity]);
        if ($event->getResult()) {
            $entity = $event->getResult();
        }

        return $entity;
    }

    /**
     * Builds entities list
     *
     * @return \Cake\Datasource\ResultSetInterface
     */
    protected function _getEntities(): ResultSetInterface
    {
        $query = $this->_getEntitiesQuery();
        if ($this->_finder !== null) {
            $query = $query->find($this->_finder);
        }

        $event = $this->dispatchEvent('Action.Crud.onFindEntities', ['query' => $query]);
        if ($event->getResult()) {
            $query = $event->getResult();
        }
        $records = $query->all();
        $event = $this->dispatchEvent('Action.Crud.afterFindEntities', ['query' => $query, 'records' => $records]);
        if ($event->getResult() !== null) {
            $records = $event->getResult();
        }

        return $records;
    }

    /**
     * Returns entiries query object
     *
     * @return \Cake\ORM\Query
     */
    protected function _getEntitiesQuery(): Query
    {
        return $this->getTable()->find();
    }

    /**
     * Returns single entity by id.
     *
     * @param mixed $primaryKey Primary key.
     * @return \Cake\Datasource\EntityInterface|array
     */
    protected function _getEntity($primaryKey)
    {
        $query = $this->_getEntityQuery($primaryKey);
        if ($this->_finder !== null) {
            $query = $query->find($this->_finder);
        }
        $event = $this->dispatchEvent('Action.Crud.onFindEntity', ['query' => $query]);
        if ($event->getResult()) {
            $query = $event->getResult();
        }

        $record = $query->firstOrFail();
        $event = $this->dispatchEvent('Action.Crud.afterFindEntity', ['query' => $query, 'record' => $record]);
        if ($event->getResult() !== null) {
            $record = $event->getResult();
        }

        return $record;
    }

    /**
     * Returns entiry query object
     *
     * @param mixed $primaryKey Primary key.
     * @return \Cake\ORM\Query
     */
    protected function _getEntityQuery($primaryKey)
    {
        return $this->getTable()->find('all')->where($this->_buildViewCondition($primaryKey));
    }

    /**
     * Build condition for get entity method.
     *
     * @param mixed $primaryKey Primary key
     * @return array
     */
    protected function _buildViewCondition($primaryKey): array
    {
        $table = $this->getTable();
        $key = (array)$table->getPrimaryKey();
        $alias = $table->getAlias();
        foreach ($key as $index => $keyname) {
            $key[$index] = $alias . '.' . $keyname;
        }
        $primaryKey = (array)$primaryKey;
        if (count($key) !== count($primaryKey)) {
            $primaryKey = $primaryKey ?: [null];
            $primaryKey = array_map(fn($key) => var_export($key, true), $primaryKey);

            $msg = sprintf(
                'Record not found in table "%s" with primary key [%s]',
                $table->getTable(),
                implode($primaryKey, ', ')
            );
            throw new InvalidPrimaryKeyException($msg);
        }

        return array_combine($key, $primaryKey);
    }

    /**
     * Save entity.
     *
     * @param \Cake\Datasource\EntityInterface $entity An Entity instance.
     * @return \Cake\Datasource\EntityInterface
     */
    protected function _save(EntityInterface $entity): EntityInterface
    {
        if ($this->getTable()->save($entity)) {
            return $entity;
        } else {
            $message = __('Validation on {0} failed', $this->getTable()->getAlias());
            throw new ValidationException($message, 0, null, $entity->getErrors());
        }
    }

    /**
     * Describe table with full details.
     *
     * @return array
     */
    protected function _describe(): array
    {
        $table = $this->getTable();
        $schema = $table->getSchema();

        $entity = $this->_newEntity();
        $reverseRouter = new ReverseRouting();
        $path = $reverseRouter->indexPath($this);
        $version = $this->getService()->getVersion();
        $actions = [
            'index' => $reverseRouter->link('self', $path, 'GET', $version),
            'add' => $reverseRouter->link('add', $path, 'POST', $version),
            'edit' => $reverseRouter->link('edit', $path . '/{id}', 'PUT', $version),
            'delete' => $reverseRouter->link('delete', $path . '/{id}', 'DELETE', $version),
        ];

        $validators = [];
        /** @var \Cake\Validation\ValidationSet $field */
        foreach ($table->getValidator()->getIterator() as $name => $field) {
            $validators[$name] = [
                'validatePresence' => $field->isPresenceRequired(),
                'emptyAllowed' => $field->isEmptyAllowed(),
                'rules' => [],
            ];
            foreach ($field->getIterator() as $ruleName => $rule) {
                /** @var \Cake\Validation\ValidationRule $rule */
                $_rule = $rule->get('rule');
                if (is_callable($_rule)) {
                    continue;
                }
                $params = $rule->get('pass');
                if (is_callable($params)) {
                    $params = null;
                }
                if (is_array($params)) {
                    foreach ($params as &$param) {
                        if (is_callable($param)) {
                            $param = null;
                        }
                    }
                }
                $validators[$name]['rules'][$ruleName] = [
                    'message' => $rule->get('message'),
                    'on' => $rule->get('on'),
                    'rule' => $_rule,
                    'params' => $params,
                    'last' => $rule->get('last'),
                ];
            }
        }

        $labels = collection($schema->columns())
            ->map(fn($column) => [
                'name' => $column,
                'label' => __(Inflector::humanize(preg_replace('/_id$/', '', $column))),
            ])
            ->combine('name', 'label')
            ->toArray();

        $associationTypes = ['BelongsTo', 'HasOne', 'HasMany', 'BelongsToMany'];
        $associations = collection($associationTypes)
            ->map(fn(string $type) => [
                'type' => $type,
                'assocs' => collection($table->associations()->getByType($type))
                    ->map(fn(Association $assoc) => $assoc->getTarget()->getTable())
                    ->toArray(),
            ])
            ->combine('type', 'assocs')
            ->toArray();

        $fieldTypes = collection($schema->columns())
            ->map(fn(string $column) => [
                'name' => $column,
                'column' => $schema->getColumn($column),
            ])
            ->combine('name', 'column')
            ->toArray();

        return [
            'entity' => [
                'hidden' => $entity->getHidden(),

                // ... fields with data types
            ],
            'schema' => [
                'columns' => $fieldTypes,
                'labels' => $labels,
            ],
            'validators' => $validators,
            'relations' => $associations,
            'actions' => $actions,
        ];
    }
}
