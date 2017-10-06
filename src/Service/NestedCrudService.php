<?php
/**
 * Copyright 2016 - 2017, Cake Development Corporation (http://cakedc.com)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright 2016 - 2017, Cake Development Corporation (http://cakedc.com)
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

namespace CakeDC\Api\Service;

use Cake\Utility\Hash;
use Cake\Utility\Inflector;

/**
 * Class NestedCrudService
 *
 * @package CakeDC\Api\Service
 */
abstract class NestedCrudService extends CrudService
{

    /**
     * @var mixed
     */
    protected $_parentIdName = null;

    /**
     * NestedCrudService constructor.
     *
     * @param array $config Service settings.
     */
    public function __construct(array $config = [])
    {
        parent::__construct($config);
        if (isset($config['parentIdName'])) {
            $this->_parentIdName = $config['parentIdName'];
        }
    }

    /**
     * Action constructor options.
     *
     * @param array $route Action route,
     * @return array
     */
    protected function _actionOptions($route)
    {
        $parent = $this->getParentService();
        if ($this->_parentIdName === null && $parent instanceof Service) {
            $parentName = $parent->getName();
            $parentIdName = Inflector::singularize($parentName) . '_id';
            if (array_key_exists($parentIdName, $route)) {
                $this->_parentIdName = $parentIdName;
            }
        }
        $parentId = null;
        if ($this->_parentIdName !== null && isset($route[$this->_parentIdName])) {
            $parentId = $route[$this->_parentIdName];
        }
        $options = [
            'parentId' => $parentId,
            'parentIdName' => $this->_parentIdName,
        ];
        if ($parentId !== null) {
            $options['Extension'] = ['CakeDC/Api.Nested'];
        }

        return Hash::merge(parent::_actionOptions($route), $options);
    }
}
