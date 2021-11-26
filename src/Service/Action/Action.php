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

use Authentication\IdentityInterface;
use Cake\Core\InstanceConfigTrait;
use Cake\Event\EventDispatcherInterface;
use Cake\Event\EventDispatcherTrait;
use Cake\Event\EventListenerInterface;
use Cake\Event\EventManager;
use Cake\Utility\Hash;
use Cake\Utility\MergeVariablesTrait;
use Cake\Validation\ValidatorAwareTrait;
use CakeDC\Api\Exception\ValidationException;
use CakeDC\Api\Service\Auth\Auth;
use CakeDC\Api\Service\Service;
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
     */
    public ?\CakeDC\Api\Service\Auth\Auth $Auth = null;

    /**
     * Default Action options.
     */
    protected array $_defaultConfig = [];

    /**
     * A Service reference.
     *
     * @var \CakeDC\Api\Service\Service
     */
    protected $_service;

    /**
     * Activated route.
     */
    protected ?array $_route = null;

    /**
     * Extension registry.
     */
    protected ?\CakeDC\Api\Service\Action\ExtensionRegistry $_extensions = null;

    /**
     * Action name.
     */
    protected ?string $_name = null;

    /**
     * Action constructor.
     *
     * @param array $config Configuration options passed to the constructor
     * @throws \Exception
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
            $this->extensions = Hash::merge($this->extensions, $config['Extension']);
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
    public function initialize(array $config): void
    {
        $this->Auth = $this->_initializeAuth();
    }

    /**
     * Gets an action name.
     *
     * @return string|null
     */
    public function getName(): ?string
    {
        return $this->_name;
    }

    /**
     * Sets an action name.
     *
     * @param string $name An action name.
     * @return self
     */
    public function setName(string $name): self
    {
        $this->_name = $name;

        return $this;
    }

    /**
     * Returns activated route.
     *
     * @return array
     */
    public function getRoute(): array
    {
        return $this->_route;
    }

    /**
     * Sets activated route.
     *
     * @param array $route Route config.
     * @return self
     */
    public function setRoute(array $route): self
    {
        $this->_route = $route;

        return $this;
    }

    /**
     * @return \CakeDC\Api\Service\Service
     */
    public function getService()
    {
        return $this->_service;
    }

    /**
     * Set a service
     *
     * @param \CakeDC\Api\Service\Service $service service
     * @return void
     */
    public function setService(Service $service): void
    {
        $this->_service = $service;
    }

    /**
     * Apply validation process.
     *
     * @return bool
     */
    public function validates(): bool
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
     * @throws \Exception
     */
    public function process()
    {
        $data = null;
        $event = $this->dispatchEvent('Action.beforeProcess', ['action' => $this]);

        if ($event->isStopped()) {
            return $event->getResult();
        }

        $event = $this->dispatchEvent('Action.onAuth', ['action' => $this]);
        if ($event->isStopped()) {
            return $event->getResult();
        }
        // $this->_initializeAuth()->authCheck($event);
        $event = $this->dispatchEvent('Action.beforeValidate', []);

        if ($event->isStopped()) {
            $this->dispatchEvent('Action.beforeValidateStopped', []);

            return $event->getResult();
        }

        if (!$this->validates()) {
            $this->dispatchEvent('Action.validationFailed', []);
            throw new ValidationException(__('Validation failed'), 0, null, []);
        }

        $event = $this->dispatchEvent('Action.beforeExecute', []);

        if ($event->isStopped()) {
            $this->dispatchEvent('Action.beforeExecuteStopped', []);

            return $event->getResult();
        }

        $result = method_exists($this, 'action') ? $this->_executeAction() : $this->execute();
        $this->dispatchEvent('Action.afterProcess', ['result' => $result]);

        return $result;
    }

    /**
     * Execute action call to the method.
     *
     * This method pass action params as method params.
     *
     * @param string $methodName Method name.
     * @return mixed
     * @throws \Exception
     */
    protected function _executeAction(string $methodName = 'action')
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
            } elseif ($params[$paramName] === '' && $param->isOptional()) {
                $arguments[] = $param->getDefaultValue();
            } else {
                $value = $params[$paramName];
                $arguments[] = is_numeric($value) ? 0 + $value : $value;
            }
        }

        return call_user_func_array([$this, $methodName], $arguments);
    }

    /**
     * @return array
     */
    public function implementedEvents(): array
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
     * @return \CakeDC\Api\Service\Action\ExtensionRegistry
     */
    public function getExtensions(): ExtensionRegistry
    {
        return $this->_extensions;
    }

    /**
     * Returns unpacked identity request attribute. Helper auth method.
     *
     * @return mixed
     */
    public function getIdentity()
    {
        $identity = $this->getService()->getRequest()->getAttribute('identity');
        if ($identity) {
            $identity = $identity instanceof IdentityInterface ? $identity->getOriginalData() : $identity;
        }

        return $identity;
    }

    /**
     * Set a service
     *
     * @param \CakeDC\Api\Service\Action\ExtensionRegistry|null $extensions Extension registry.
     * @return self
     */
    public function setExtensions(?ExtensionRegistry $extensions = null): self
    {
        if ($extensions === null && $this->_extensions === null) {
            $this->_extensions = new ExtensionRegistry($this);
        } else {
            $this->_extensions = $extensions;
        }

        return $this;
    }

    /**
     * Loads the defined extensions using the Extension factory.
     *
     * @return void
     * @throws \Exception
     */
    protected function _loadExtensions(): void
    {
        if (empty($this->extensions)) {
            return;
        }
        $registry = $this->getExtensions();
        $this->_mergeVars(['extensions'], ['associative' => ['extensions']]);
        foreach ($this->extensions as $key => $value) {
            if (is_string($key) && $value === false) {
                unset($this->extensions[$key]);
            }
        }

        $extensions = $registry->normalizeArray($this->extensions);
        foreach ($extensions as $name => $properties) {
            if ($properties === false) {
                continue;
            }
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
     * @return \CakeDC\Api\Service\Auth\Auth
     */
    protected function _initializeAuth(): Auth
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
    protected function _authConfig(): array
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
