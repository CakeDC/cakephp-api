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
use Cake\Http\ServerRequest;
use Cake\Utility\Hash;

trait AuthorizeTrait
{
    /**
     * Objects that will be used for authorization checks.
     *
     * @var array
     */
    protected $_authorizeObjects = [];

    /**
     * The instance of the Authorize provider that was used to grant
     * access to the current user to the URL they are requesting.
     *
     * @var \Cake\Auth\BaseAuthorize
     */
    protected $_authorizationProvider;

    /**
     * Check if the provided user is authorized for the request.
     *
     * Uses the configured Authorization adapters to check whether or not a user is authorized.
     * Each adapter will be checked in sequence, if any of them return true, then the user will
     * be authorized for the request.
     *
     * @param array|null $user The user to check the authorization of.
     *   If empty the user fetched from storage will be used.
     * @param \Cake\Http\ServerRequest|null $request The request to authenticate for.
     *   If empty, the current request will be used.
     * @return bool True if $user is authorized, otherwise false
     */
    public function isAuthorized($user = null, ?ServerRequest $request = null)
    {
        if (empty($user) && !$this->user()) {
            return false;
        }
        if (empty($user)) {
            $user = $this->user();
        }
        if (empty($request)) {
            $request = $this->request;
        }
        if (empty($this->_authorizeObjects)) {
            $this->constructAuthorize();
        }
        foreach ($this->_authorizeObjects as $authorizer) {
            if ($authorizer->authorize($user, $request) === true) {
                $this->_authorizationProvider = $authorizer;

                return true;
            }
        }

        return false;
    }

    /**
     * Loads the authorization objects configured.
     *
     * @return array|null The loaded authorization objects, or null when authorize is empty.
     * @throws \Cake\Core\Exception\CakeException
     */
    public function constructAuthorize()
    {
        if (empty($this->_config['authorize'])) {
            return null;
        }
        $this->_authorizeObjects = [];
        $authorize = Hash::normalize((array)$this->_config['authorize']);
        $global = [];
        if (isset($authorize[AuthComponent::ALL])) {
            $global = $authorize[AuthComponent::ALL];
            unset($authorize[AuthComponent::ALL]);
        }
        foreach ($authorize as $alias => $config) {
            if (!empty($config['className'])) {
                $class = $config['className'];
                unset($config['className']);
            } else {
                $class = $alias;
            }
            $className = App::className($class, 'Service/Auth/Authorize', 'Authorize');
            if (!class_exists($className)) {
                throw new \Cake\Core\Exception\CakeException(sprintf('Authorization adapter "%s" was not found.', $class));
            }
            $config = (array)$config + $global;
            $class = new $className($this->_action, $config);
            if (!method_exists($class, 'authorize')) {
                throw new \Cake\Core\Exception\CakeException('Authorization objects must implement an authorize() method.');
            }
            $this->_authorizeObjects[$alias] = $class;
        }

        return $this->_authorizeObjects;
    }

    /**
     * Getter for authorize objects. Will return a particular authorize object.
     *
     * @param string $alias Alias for the authorize object
     * @return \Cake\Auth\BaseAuthorize|null
     */
    public function getAuthorize($alias)
    {
        if (empty($this->_authorizeObjects)) {
            $this->constructAuthorize();
        }

        return $this->_authorizeObjects[$alias] ?? null;
    }

    /**
     * If there was any authorization processing for the current request, this function
     * will return the instance of the Authorization object that granted access to the
     * user to the current address.
     *
     * @return \Cake\Auth\BaseAuthorize|null
     */
    public function authorizationProvider()
    {
        return $this->_authorizationProvider;
    }
}
