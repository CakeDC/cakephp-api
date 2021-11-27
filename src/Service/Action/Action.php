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

namespace CakeDC\Api\Service\Action;

use CakeDC\Api\Exception\ValidationException;
use CakeDC\Api\Service\Auth\Auth;
use CakeDC\Api\Service\Service;
use Cake\Core\InstanceConfigTrait;
use Cake\Event\EventDispatcherInterface;
use Cake\Event\EventDispatcherTrait;
use Cake\Event\EventListenerInterface;
use Cake\Event\EventManager;
use Cake\Utility\Hash;
use Cake\Utility\MergeVariablesTrait;
use Cake\Validation\ValidatorAwareTrait;
use Exception;
use ReflectionMethod;

/**
 * Class Action
 *
 * @package CakeDC\Api\Service\Action
 */
abstract class Action implements EventListenerInterface, EventDispatcherInterface
{
    use EventDispatcherTrait;
    use InstanceConfigTrait;
    use MergeVariablesTrait;
    use ValidatorAwareTrait;

    /**
     * Extensions to load and attach to listener
     *
     * @var array
     */
    public $extensions = [];

    /**
     * An Auth instance.
     *
     * @var Auth
     */
    public $Auth;

    /**
     * Default Action options.
     *
     * @var array
     */
    protected $_defaultConfig = [];

    /**
     * A Service reference.
     *
     * @var Service
     */
    protected $_service;

    /**
     * Activated route.
     *
     * @var array
     */
    protected $_route = null;

    /**
     * Extension registry.
     *
     * @var \CakeDC\Api\Service\Action\ExtensionRegistry
     */
    protected $_extensions;

    /**
     * Action name.
     *
     * @var string
     */
    protected $_name;

    /**
     * Action constructor.
     *
     * @param array $config Configuration options passed to the constructor
     */
    public function __construct(array $config = [])
    {
        if (!empty($config['name'])) {
            $this->setName($config['name']);
        }
        if (!empty($config['service'])) {
            $this->setService($config['service']);
        }
        if (!empty($config['route'])) {
            $this->setRoute($config['route']);
        }
        if (!empty($config['Extension'])) {
            $this->extensions = (Hash::merge($this->extensions, $config['Extension']));
        }
        $extensionRegistry = $eventManager = null;
        if (!empty($config['eventManager'])) {
            $eventManager = $config['eventManager'];
        }
        $this->_eventManager = $eventManager ?: new EventManager();
        $this->setConfig($config);
        $this->initialize($config);
        $this->_eventManager->on($this);
        $this->setExtensions($extensionRegistry);
        $this->_loadExtensions();
    }

    /**
     * Initialize an action instance
     *
     * @param array $config Configuration options passed to the constructor
     * @return void
     */
    public function initialize(array $config)
    {
        $this->Auth = $this->_initializeAuth();
    }

    /**
     * Gets an action name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->_name;
    }

    /**
     * Sets an action name.
     *
     * @param string $name An action name.
     * @return $this
     */
    public function setName($name)
    {
        $this->_name = $name;

        return $this;
    }

    /**
     * Get and set service name.
     *
     * @param string $name Action name.
     * @deprecated 3.4.0 Use setName()/getName() instead.
     * @return string
     */
    public function name($name = null)
    {
        deprecationWarning(
            'Action::name() is deprecated. ' .
            'Use Action::setName()/getName() instead.'
        );

        if ($name !== null) {
            return $this->setName($name);
        }

        return $this->getName();
    }

    /**
     * Returns activated route.
     *
     * @return array
     */
    public function getRoute()
    {
        return $this->_route;
    }

    /**
     * Sets activated route.
     *
     * @param array $route Route config.
     * @return $this
     */
    public function setRoute(array $route)
    {
        $this->_route = $route;

        return $this;
    }

    /**
     * Api method for activated route.
     *
     * @param array $route Activated route.
     * @deprecated 3.4.0 Use setRoute()/getRoute() instead.
     * @return array
     */
    public function route($route = null)
    {
        deprecationWarning(
            'Action::route() is deprecated. ' .
            'Use Action::setRoute()/getRoute() instead.'
        );

        if ($route !== null) {
            return $this->setRoute($route);
        }

        return $this->getRoute();
    }

    /**
     * @return Service
     */
    public function getService()
    {
        return $this->_service;
    }

    /**
     * Set a service
     *
     * @param Service $service service
     */
    public function setService(Service $service)
    {
        $this->_service = $service;
    }

    /**
     * Set or get service.
     *
     * @param Service $service An Service instance.
     * @return Service
     * @deprecated 3.4.0 Use setService()/getService() instead.
     */
    public function service($service = null)
    {
        deprecationWarning(
            'Action::service() is deprecated. ' .
            'Use Action::setService()/getService() instead.'
        );

        if ($service !== null) {
            return $this->setService($service);
        }

        return $this->getService();
    }

    /**
     * Apply validation process.
     *
     * @return bool
     */
    public function validates()
    {
        return true;
    }

    /**
     * Execute action.
     *
     * @return mixed
     */
    abstract public function execute();

    /**
     * Action execution life cycle.
     *
     * @return mixed
     */
    public function process()
    {
        $event = $this->dispatchEvent('Action.beforeProcess', ['action' => $this]);

        if ($event->isStopped()) {
            return $event->result;
        }

        $event = $this->dispatchEvent('Action.onAuth', ['action' => $this]);
        if ($event->isStopped()) {
            return $event->getResult();
        }
        // $this->_initializeAuth()->authCheck($event);
        $event = $this->dispatchEvent('Action.beforeValidate', []);

        if ($event->isStopped()) {
            $this->dispatchEvent('Action.beforeValidateStopped', []);

            return $event->result;
        }

        if (!$this->validates()) {
            $this->dispatchEvent('Action.validationFailed', []);
            throw new ValidationException(__('Validation failed'), 0, null, []);
        }

        $event = $this->dispatchEvent('Action.beforeExecute', []);

        if ($event->isStopped()) {
            $this->dispatchEvent('Action.beforeExecuteStopped', []);

            return $event->result;
        }

        // thrown before execute action event (with stop on false)
        if (method_exists($this, 'action')) {
            $result = $this->_executeAction();
        } else {
            $result = $this->execute();
        }
        $this->dispatchEvent('Action.afterProcess', compact('result'));

        return $result;
    }

    /**
     * Execute action call to the method.
     *
     * This method pass action params as method params.
     *
     * @param string $methodName Method name.
     * @return mixed
     * @throws Exception
     */
    protected function _executeAction($methodName = 'action')
    {
        $parser = $this->getService()->getParser();
        $params = $parser->getParams();
        $arguments = [];
        $reflection = new ReflectionMethod($this, $methodName);
        foreach ($reflection->getParameters() as $param) {
            $paramName = $param->getName();
            if (!isset($params[$paramName])) {
                if ($param->isOptional()) {
                    $arguments[] = $param->getDefaultValue();
                } else {
                    throw new Exception('Missing required param: ' . $paramName, 409);
                }
            } else {
                if ($params[$paramName] === '' && $param->isOptional()) {
                    $arguments[] = $param->getDefaultValue();
                } else {
                    $value = $params[$paramName];
                    $arguments[] = (is_numeric($value)) ? 0 + $value : $value;
                }
            }
        }
        $result = call_user_func_array([$this, $methodName], $arguments);

        return $result;
    }

    /**
     * @return array
     */
    public function implementedEvents()
    {
        $eventMap = [
            'Action.beforeProcess' => 'beforeProcess',
            'Action.beforeValidate' => 'beforeValidate',
            'Action.beforeExecute' => 'beforeExecute',
            'Action.afterProcess' => 'afterProcess',
        ];
        $events = [];

        foreach ($eventMap as $event => $method) {
            if (!method_exists($this, $method)) {
                continue;
            }
            $events[$event] = $method;
        }

        return $events;
    }

    /**
     * Returns action input params
     *
     * @param string|null $name Dot separated name of the value to read. Or null to read all data.
     * @param mixed $default The default data.
     * @return mixed The value being read.
     */
    public function getData($name = null, $default = null)
    {
        $data = $this->getService()->getParser()->getParams();
        if ($name === null) {
            return $data;
        }
        if (!is_array($data) && $name) {
            return $default;
        }

        /** @psalm-suppress PossiblyNullArgument */
        return Hash::get($data, $name, $default);
    }

    /**
     * Returns action input params
     *
     * @return mixed
     * @deprecated 3.6.0 Use getData() instead.
     */
    public function data()
    {
        deprecationWarning(
            'Action::data() is deprecated. ' .
            'Use Action::getData() instead.'
        );

        return $this->getData();
    }

    /**
     * @return \CakeDC\Api\Service\Action\ExtensionRegistry
     */
    public function getExtensions()
    {
        return $this->_extensions;
    }

    /**
     * Set a service
     *
     * @param \CakeDC\Api\Service\Action\ExtensionRegistry|null $extensions Extension registry.
     */
    public function setExtensions($extensions = null)
    {
        if ($extensions === null && $this->_extensions === null) {
            $this->_extensions = new ExtensionRegistry($this);
        } else {
            $this->_extensions = $extensions;
        }

        return $this;
    }

    /**
     * Get the extension registry for this action.
     *
     * If called with the first parameter, it will be set as the action $this->_extensions property
     *
     * @param \CakeDC\Api\Service\Action\ExtensionRegistry|null $extensions Extension registry.
     *
     * @return \CakeDC\Api\Service\Action\ExtensionRegistry
     * @deprecated 3.6.0 Use setExtensions()/getExtensions() instead.
     */
    public function extensions($extensions = null)
    {
        deprecationWarning(
            'Action::extensions() is deprecated. ' .
            'Use Action::setExtensions()/getExtensions() instead.'
        );

        if ($this->_extensions === null && $extensions === null) {
            $this->setExtensions($extensions);

            return $this->getExtensions();
        }
    }

    /**
     * Loads the defined extensions using the Extension factory.
     *
     * @return void
     */
    protected function _loadExtensions()
    {
        if (empty($this->extensions)) {
            return;
        }
        $registry = $this->getExtensions();
        $this->_mergeVars(['extensions'], ['associative' => ['extensions']]);
        $extensions = $registry->normalizeArray($this->extensions);
        foreach ($extensions as $name => $properties) {
            $instance = $registry->load($properties['class'], $properties['config']);
            $this->_eventManager->on($instance);
            if ($instance->attachable()) {
                [, $prop] = pluginSplit($name);
                $this->{$prop} = $instance;
            }
        }
    }

    /**
     * Initialize auth.
     *
     * @return Auth
     */
    protected function _initializeAuth()
    {
        $config = $this->_authConfig();
        $auth = new Auth($config);
        if (array_key_exists('allow', $config)) {
            $auth->allow($config['allow']);

            return $auth;
        }

        return $auth;
    }

    /**
     * Prepare Auth configuration.
     *
     * @return array
     */
    protected function _authConfig()
    {
        $defaultConfig = (array)$this->getConfig('Auth');

        return Hash::merge($defaultConfig, [
            'service' => $this->_service,
            'request' => $this->_service->getRequest(),
            'response' => $this->_service->getResponse(),
            'action' => $this,
        ]);
    }
}
