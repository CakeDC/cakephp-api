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

use Cake\Core\InstanceConfigTrait;
use Cake\Event\EventDispatcherTrait;
use Cake\Http\Response;
use Cake\Http\ServerRequest;
use Cake\Log\LogTrait;
use CakeDC\Api\Service\Action\Action;

/**
 * Class Auth
 *
 * @package CakeDC\Api\Service\Auth
 */
class Auth
{
    use AuthenticateTrait;
    /**
     * @use \Cake\Event\EventDispatcherTrait<\CakeDC\Api\Service\Auth\Auth>
     */
    use EventDispatcherTrait;
    use InstanceConfigTrait;
    use LogTrait;

    /**
     * Constant for 'all'
     *
     * @var string
     */
    public const ALL = 'all';

    /**
     * Actions for which user validation is not required.
     */
    public array $allowedActions = [];

    /**
     * Request object
     *
     * @var \Cake\Http\ServerRequest
     */
    public ?ServerRequest $request;

    /**
     * Response object
     *
     * @var \Cake\Http\Response
     */
    public ?Response $response;

    /**
     * Default config
     *
     * These are merged with user-provided config when the component is used.
     */
    protected array $_defaultConfig = [
        'storage' => 'Memory',
        'identityAttribute' => 'identity',
    ];

    protected $_registry = null;

    /**
     * @var \CakeDC\Api\Service\Service
     */
    protected $_service;

    /**
     * @var \CakeDC\Api\Service\Action\Action
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
        $this->setEventManager($this->_action->getEventManager());
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
        'authError' => __d('CakeDC/Api', 'You are not authorized to access that location.'),
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
     * Checks whether current action is accessible without authentication.
     *
     * @param \CakeDC\Api\Service\Action\Action $action An Action instance.
     * @return bool True if action is accessible without authentication else false
     */
    protected function _isAllowed(Action $action)
    {
        $action = strtolower($action->getName());

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

    /**
     * Returns Request
     *
     * @return ?\Cake\Http\ServerRequest
     */
    public function getRequest(): ?ServerRequest
    {
        return $this->request;
    }

    /**
     * Returns Response
     *
     * @return ?\Cake\Http\Response
     */
    public function getResponse(): ?Response
    {
        return $this->response;
    }

    /**
     * Sets request object.
     *
     * @param ?\Cake\Http\ServerRequest $request ServerRequest object.
     * @return void
     */
    public function setRequest(?ServerRequest $request): void
    {
        $this->request = $request;
    }

    /**
     * Sets response object.
     *
     * @param ?\Cake\Http\Response $response Response object.
     * @return void
     */
    public function setResponse(?Response $response): void
    {
        $this->response = $response;
    }
}
