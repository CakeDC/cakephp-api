<?php
/**
 * Copyright 2016, Cake Development Corporation (http://cakedc.com)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright 2016, Cake Development Corporation (http://cakedc.com)
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

namespace CakeDC\Api\Service;

use Cake\Utility\Inflector;

/**
 * Class CrudService
 *
 * @package CakeDC\Api\Service
 */
abstract class CrudService extends Service
{

    /**
     * Actions classes map.
     *
     * @var array
     */
    protected $_actionsClassMap = [
        'describe' => '\CakeDC\Api\Service\Action\CrudDescribeAction',
        'index' => '\CakeDC\Api\Service\Action\CrudIndexAction',
        'view' => '\CakeDC\Api\Service\Action\CrudViewAction',
        'add' => '\CakeDC\Api\Service\Action\CrudAddAction',
        'edit' => '\CakeDC\Api\Service\Action\CrudEditAction',
        'delete' => '\CakeDC\Api\Service\Action\CrudDeleteAction',
    ];

    /**
     * Table name.
     *
     * @var string
     */
    protected $_table = null;

    /**
     * Id param name.
     *
     * @var string
     */
    protected $_idName = 'id';

    /**
     * CrudService constructor.
     *
     * @param array $config Service configuration.
     */
    public function __construct(array $config = [])
    {
        parent::__construct($config);
        if (isset($config['table'])) {
            $this->table($config['table']);
        } else {
            $this->table(Inflector::camelize($this->name()));
        }
    }

    /**
     * Table getter and setter method.
     *
     * @param string $table Table name.
     * @return string
     */
    public function table($table = null)
    {
        if ($table === null) {
            return $this->_table;
        }
        $this->_table = $table;

        return $this->_table;
    }

    /**
     * Action constructor options.
     *
     * @param array $route Activated route.
     * @return array
     */
    protected function _actionOptions($route)
    {
        $id = null;
        if (isset($route[$this->_idName])) {
            $id = $route[$this->_idName];
        }

        return parent::_actionOptions($route) + [
            'id' => $id,
            'idName' => $this->_idName,
        ];
    }
}
