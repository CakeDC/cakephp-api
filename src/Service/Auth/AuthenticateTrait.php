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

use Cake\Controller\Component\AuthComponent;
use Cake\Core\App;
use Cake\Core\Exception\Exception;
use Cake\Utility\Hash;

/**
 * Class AuthenticateTrait
 *
 * @package CakeDC\Api\Service\Auth
 */
trait AuthenticateTrait
{
    /**
     * Objects that will be used for authentication checks.
     *
     * @var array
     */
    protected $_authenticateObjects = [];

    /**
     * The instance of the Authenticate provider that was used for
     * successfully logging in the current user after calling `login()`
     * in the same request
     *
     * @var \Cake\Auth\BaseAuthenticate
     */
    protected $_authenticationProvider;

    /**
     * Get the current user from storage.
     *
     * @param string|null $key Field to retrieve. Leave null to get entire User record.
     * @return mixed|null Either User record or null if no user is logged in, or retrieved field if key is specified.
     */
    public function user($key = null)
    {
        $user = $this->storage()->read();
        if (!$user) {
            return null;
        }

        if ($key === null) {
            return $user;
        }

        return Hash::get($user, $key);
    }

    /**
     * Set provided user info to storage as logged in user.
     *
     * The storage class is configured using `storage` config key or passing
     * instance to AuthComponent::storage().
     *
     * @param array $user Array of user data.
     * @return void
     */
    public function setUser(array $user)
    {
        $this->storage()->write($user);
    }

    /**
     * connected authentication objects will have their
     * getUser() methods called.
     *
     * This lets stateless authentication methods function correctly.
     *
     * @return bool true If a user can be found, false if one cannot.
     */
    protected function _getUser()
    {
        $user = $this->user();
        if ($user) {
            $this->storage()->redirectUrl(false);

            return true;
        }

        if (empty($this->_authenticateObjects)) {
            $this->constructAuthenticate();
        }
        foreach ($this->_authenticateObjects as $auth) {
            $result = $auth->getUser($this->request);
            if (!empty($result) && is_array($result)) {
                $this->_authenticationProvider = $auth;
                $event = $this->dispatchEvent('Auth.afterIdentify', [$result, $auth]);
                if ($event->getResult() !== null) {
                    $result = $event->getResult();
                }
                $this->storage()->write($result);

                return true;
            }
        }

        return false;
    }

    /**
     * Use the configured authentication adapters, and attempt to identify the user
     * by credentials contained in $request.
     *
     * Triggers `Auth.afterIdentify` event which the authenticate classes can listen
     * to.
     *
     * @return \Cake\Datasource\EntityInterface|array|null User record data, or false, if the user could not be identified.
     */
    public function identify()
    {
//        $this->_setDefaults();

        if (empty($this->_authenticateObjects)) {
            $this->constructAuthenticate();
        }
        foreach ($this->_authenticateObjects as $auth) {
            $result = $auth->authenticate($this->request, $this->response);
            if (!empty($result) && is_array($result)) {
                $this->_authenticationProvider = $auth;
                $event = $this->dispatchEvent('Auth.afterIdentify', [$result, $auth]);
                if ($event->getResult() !== null) {
                    return $event->getResult();
                }

                return $result;
            }
        }

        return null;
    }

    /**
     * Loads the configured authentication objects.
     *
     * @return array|null The loaded authorization objects, or null on empty authenticate value.
     * @throws \Cake\Core\Exception\Exception
     */
    public function constructAuthenticate()
    {
        if (empty($this->_config['authenticate'])) {
            return null;
        }
        $this->_authenticateObjects = [];
        $authenticate = Hash::normalize((array)$this->_config['authenticate']);
        $global = [];
        if (isset($authenticate[AuthComponent::ALL])) {
            $global = $authenticate[AuthComponent::ALL];
            unset($authenticate[AuthComponent::ALL]);
        }
        foreach ($authenticate as $alias => $config) {
            if (!empty($config['className'])) {
                $class = $config['className'];
                unset($config['className']);
            } else {
                $class = $alias;
            }
            $className = App::className($class, 'Service/Auth/Authenticate', 'Authenticate');
            if (!class_exists($className)) {
                throw new Exception(sprintf('Authentication adapter "%s" was not found.', $class));
            }
            if (!method_exists($className, 'authenticate')) {
                throw new Exception('Authentication objects must implement an authenticate() method.');
            }
            $config = array_merge($global, (array)$config);
            $this->_authenticateObjects[$alias] = new $className($this->_action, $config);
            $this->getEventManager()->on($this->_authenticateObjects[$alias]);
        }

        return $this->_authenticateObjects;
    }

    /**
     * Getter for authenticate objects. Will return a particular authenticate object.
     *
     * @param string $alias Alias for the authenticate object
     *
     * @return \Cake\Auth\BaseAuthenticate|null
     */
    public function getAuthenticate($alias)
    {
        if (empty($this->_authenticateObjects)) {
            $this->constructAuthenticate();
        }

        return $this->_authenticateObjects[$alias] ?? null;
    }

    /**
     * If login was called during this request and the user was successfully
     * authenticated, this function will return the instance of the authentication
     * object that was used for logging the user in.
     *
     * @return \Cake\Auth\BaseAuthenticate|null
     */
    public function authenticationProvider()
    {
        return $this->_authenticationProvider;
    }
}
