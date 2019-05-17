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
use Cake\ORM\Association;
use Cake\ORM\ResultSet;
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
    /**
     * @var \Cake\ORM\Table
     */
    protected $_table = null;

    /**
     * Object Identifier
     *
     * @var string
     */
    protected $_id = null;

    protected $_idName = 'id';

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
     * @var string
     */
    protected $_parentId = null;

    protected $_parentIdName = null;

    /**
     * Api table finder method
     *
     * @var string
     */
    protected $_finder = null;

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
        if (!empty($config['table'])) {
            $tableName = $config['table'];
        } else {
            $tableName = $this->getService()->getTable();
        }
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
    public function getTable(): \Cake\ORM\Table
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
    public function getId(): string
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
    public function getParentId(): string
    {
        return $this->_parentId;
    }

    /**
     * Parent model id field name getter.
     *
     * @return mixed|string
     */
    public function getParentIdName()
    {
        return $this->_parentIdName;
    }

    /**
     * Builds new entity instance.
     *
     * @return \Cake\Datasource\EntityInterface
     */
    protected function _newEntity(): \Cake\Datasource\EntityInterface
    {
        $entity = $this->getTable()->newEntity([]);

        return $entity;
    }

    /**
     * Patch entity.
     *
     * @param \Cake\Datasource\EntityInterface $entity An Entity instance.
     * @param array $data Entity data.
     * @param array $options Patch entity options.
     * @return \Cake\Datasource\EntityInterface|mixed
     */
    protected function _patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
    {
        $entity = $this->getTable()->patchEntity($entity, $data, $options);
        $event = $this->dispatchEvent('Action.Crud.onPatchEntity', compact('entity'));
        if ($event->getResult()) {
            $entity = $event->getResult();
        }

        return $entity;
    }

    /**
     * Builds entities list
     *
     * @return \Cake\ORM\ResultSet
     */
    protected function _getEntities(): ResultSet
    {
        $query = $this->getTable()->find();
        if ($this->_finder !== null) {
            $query = $query->find($this->_finder);
        }

        $event = $this->dispatchEvent('Action.Crud.onFindEntities', compact('query'));
        if ($event->getResult()) {
            $query = $event->getResult();
        }
        $records = $query->all();
        $this->dispatchEvent('Action.Crud.afterFindEntities', compact('query', 'records'));

        return $records;
    }

    /**
     * Returns single entity by id.
     *
     * @param mixed $primaryKey Primary key.
     * @return \Cake\Datasource\EntityInterface|array
     */
    protected function _getEntity($primaryKey)
    {
        $query = $this->getTable()->find('all')->where($this->_buildViewCondition($primaryKey));
        if ($this->_finder !== null) {
            $query = $query->find($this->_finder);
        }
        $event = $this->dispatchEvent('Action.Crud.onFindEntity', compact('query'));
        if ($event->getResult()) {
            $query = $event->getResult();
        }
        $entity = $query->firstOrFail();

        return $entity;
    }

    /**
     * Build condition for get entity method.
     *
     * @param string|int $primaryKey Primary key
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
            $primaryKey = array_map(function ($key) {
                return var_export($key, true);
            }, $primaryKey);

            $msg = sprintf(
                'Record not found in table "%s" with primary key [%s]',
                $table->getTable(),
                implode($primaryKey, ', ')
            );
            throw new InvalidPrimaryKeyException($msg);
        }
        $conditions = array_combine($key, $primaryKey);

        return $conditions;
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
        $actions = [
            'index' => $reverseRouter->link('self', $path, 'GET'),
            'add' => $reverseRouter->link('add', $path, 'POST'),
            'edit' => $reverseRouter->link('edit', $path . '/{id}', 'PUT'),
            'delete' => $reverseRouter->link('delete', $path . '/{id}', 'DELETE'),
        ];

        $validators = [];
        foreach ($table->getValidator()
                       ->getIterator() as $name => $field) {
            /** @var \Cake\Validation\ValidationSet $field */
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
            ->map(function ($column) {
                return [
                    'name' => $column,
                    'label' => __(Inflector::humanize(preg_replace('/_id$/', '', $column))),
                ];
            })
            ->combine('name', 'label')
            ->toArray();

        $associationTypes = ['BelongsTo', 'HasOne', 'HasMany', 'BelongsToMany'];
        $associations = collection($associationTypes)
            ->map(function (string $type) use ($table) {
                return [
                    'type' => $type,
                    'assocs' => collection($table->associations()->getByType($type))
                        ->map(function (Association $assoc) {
                            return $assoc->getTarget()->getTable();
                        })
                        ->toArray(),
                ];
            })
            ->combine('type', 'assocs')
            ->toArray();

        $fieldTypes = collection($schema->columns())
            ->map(function (string $column) use ($schema) {
                return [
                    'name' => $column,
                    'column' => $schema->getColumn($column),
                ];
            })
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
