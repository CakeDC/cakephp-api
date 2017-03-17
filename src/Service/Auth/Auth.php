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

use CakeDC\Api\Exception\UnauthenticatedException;
use CakeDC\Api\Service\Action\Action;
use CakeDC\Api\Service\Service;
use Cake\Core\InstanceConfigTrait;
use Cake\Event\Event;
use Cake\Event\EventDispatcherTrait;
use Cake\Log\LogTrait;
use Cake\Network\Exception\ForbiddenException;
use Cake\Network\Response;

/**
 * Class Auth
 *
 * @package CakeDC\Api\Service\Auth
 */
class Auth
{
    use AuthenticateTrait;
    use AuthorizeTrait;
    use EventDispatcherTrait;
    use InstanceConfigTrait;
    use LogTrait;
    use StorageTrait;

    /**
     * Actions for which user validation is not required.
     *
     * @var array
     */
    public $allowedActions = [];

    /**
     * Request object
     *
     * @var \Cake\Network\Request
     */
    public $request;

    /**
     * Response object
     *
     * @var \Cake\Network\Response
     */
    public $response;

    /**
     * Default config
     *
     * These are merged with user-provided config when the component is used.
     *
     * @var array
     */
    protected $_defaultConfig = [
        'storage' => 'Memory',
    ];

    protected $_registry = null;

    /**
     * @var Service
     */
    protected $_service;

    /**
     * @var Action
     */
    protected $_action;

    /**
     * Constructor
     *
     * @param array $config Array of configuration settings.
     */
    public function __construct(array $config = [])
    {
        if (array_key_exists('request', $config)) {
            $this->request = $config['request'];
        }
        if (array_key_exists('response', $config)) {
            $this->response = $config['response'];
        }
        $this->setConfig($config);
        $this->initialize($config);
    }

    /**
     * Initialize properties.
     *
     * @param array $config The config data.
     * @return void
     */
    public function initialize(array $config)
    {
        if (array_key_exists('service', $config)) {
            $this->_service = $config['service'];
        }
        if (array_key_exists('action', $config)) {
            $this->_action = $config['action'];
        }
        $this->eventManager($this->_action->eventManager());
    }

    /**
     * Sets defaults for configs.
     *
     * @return void
     */
    protected function _setDefaults()
    {
        $defaults = [
            'authenticate' => ['CakeDC/Api.Token'],
            'authError' => __d('CakeDC/Api', 'You are not authorized to access that location.')
        ];

        $config = $this->getConfig();
        foreach ($config as $key => $value) {
            if ($value !== null) {
                unset($defaults[$key]);
            }
        }
        $this->setConfig($defaults);
    }

    /**
     * Takes a list of actions in the current controller for which authentication is not required, or
     * no parameters to allow all actions.
     *
     * You can use allow with either an array or a simple string.
     *
     * ```
     * $this->Auth->allow('view');
     * $this->Auth->allow(['edit', 'add']);
     *
     * @param string|array $actions Controller action name or array of actions
     * @return void
     */
    public function allow($actions)
    {
        $this->allowedActions = array_merge($this->allowedActions, (array)$actions);
    }

    /**
     * Removes items from the list of allowed/no authentication required actions.
     *
     * You can use deny with either an array or a simple string.
     *
     * ```
     * $this->Auth->deny('view');
     * $this->Auth->deny(['edit', 'add']);
     * ```
     * or
     * ```
     * $this->Auth->deny();
     * ```
     * to remove all items from the allowed list
     *
     * @param string|array|null $actions Controller action name or array of actions
     * @return void
     */
    public function deny($actions = null)
    {
        if ($actions === null) {
            $this->allowedActions = [];

            return;
        }
        foreach ((array)$actions as $action) {
            $i = array_search($action, $this->allowedActions);
            if (is_int($i)) {
                unset($this->allowedActions[$i]);
            }
        }
        $this->allowedActions = array_values($this->allowedActions);
    }

    /**
     * Main execution method, handles initial authentication check and redirection
     * of invalid users.
     *
     * The auth check is done when event name is same as the one configured in
     * `checkAuthIn` config.
     *
     * @param \Cake\Event\Event $event Event instance.
     * @return Response|null
     */
    public function authCheck(Event $event)
    {
        $action = $this->_action;

        $this->_setDefaults();

        if ($this->_isAllowed($action)) {
            return null;
        }

        if (!$this->_getUser()) {
            throw new UnauthenticatedException();
        }

        if (empty($this->_config['authorize']) ||
            $this->isAuthorized($this->user())
        ) {
            return null;
        }

        throw new ForbiddenException($this->_config['authError']);
    }

    /**
     * Checks whether current action is accessible without authentication.
     *
     * @param Action $action An Action instance.
     * @return bool True if action is accessible without authentication else false
     */
    protected function _isAllowed(Action $action)
    {
        $action = strtolower($action->name());

        return in_array($action, array_map('strtolower', $this->allowedActions)) ||
            in_array('*', $this->allowedActions);
    }

    /**
     * __get method this method will return an attribute of this class
     *
     * @param string $name Name
     * @return mixed
     */
    public function __get($name)
    {
        if (isset($this->{$name})) {
            return $this->{$name};
        }
        return null;
    }

    /**
     * __set method this method will allow you set the value for an attribute of this class
     *
     * @param string $name name of the attribute
     * @param string $value value of the attribute
     * @return void
     */
    public function __set($name, $value)
    {
        $this->{$name} = $value;
    }
}
