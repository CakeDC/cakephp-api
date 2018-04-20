<?php
/**
 * Copyright 2016 - 2018, Cake Development Corporation (http://cakedc.com)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright 2016 - 2018, Cake Development Corporation (http://cakedc.com)
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         0.10.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */

namespace CakeDC\Api\Service\Auth;

use Cake\Auth\Storage\StorageInterface;
use Cake\Core\App;
use Cake\Core\Exception\Exception;

trait StorageTrait
{

    /**
     * Storage object.
     *
     * @var \Cake\Auth\Storage\StorageInterface
     */
    protected $_storage;

    /**
     * Get/set user record storage object.
     *
     * @param \Cake\Auth\Storage\StorageInterface|null $storage Sets provided
     *   object as storage or if null returns configured storage object.
     * @return \Cake\Auth\Storage\StorageInterface|null
     */
    public function storage(StorageInterface $storage = null)
    {
        if ($storage !== null) {
            $this->_storage = $storage;

            return null;
        }

        if ($this->_storage) {
            return $this->_storage;
        }

        $config = $this->_config['storage'];
        if (is_string($config)) {
            $class = $config;
            $config = [];
        } else {
            $class = $config['className'];
            unset($config['className']);
        }
        $className = App::className($class, 'Auth/Storage', 'Storage');
        if (!class_exists($className)) {
            throw new Exception(sprintf('Auth storage adapter "%s" was not found.', $class));
        }
        $this->_storage = new $className($this->request, $this->response, $config);

        return $this->_storage;
    }
}
