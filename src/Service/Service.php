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

namespace CakeDC\Api\Service;

use Cake\Core\App;
use Cake\Core\Configure;
use Cake\Datasource\Exception\RecordNotFoundException;
use Cake\Event\EventDispatcherInterface;
use Cake\Event\EventDispatcherTrait;
use Cake\Event\EventListenerInterface;
use Cake\Event\EventManager;
use Cake\Http\Response;
use Cake\Http\ServerRequest;
use Cake\Routing\RouteBuilder;
use Cake\Utility\Hash;
use Cake\Utility\Inflector;
use CakeDC\Api\Exception\ValidationException;
use CakeDC\Api\Routing\ApiRouter;
use CakeDC\Api\Service\Action\Action;
use CakeDC\Api\Service\Action\DummyAction;
use CakeDC\Api\Service\Action\Result;
use CakeDC\Api\Service\Exception\MissingActionException;
use CakeDC\Api\Service\Exception\MissingParserException;
use CakeDC\Api\Service\Exception\MissingRendererException;
use CakeDC\Api\Service\Renderer\BaseRenderer;
use CakeDC\Api\Service\RequestParser\BaseParser;
use Exception;

/**
 * Class Service
 */
abstract class Service implements EventListenerInterface, EventDispatcherInterface
{
    use EventDispatcherTrait;

    /**
     * Extensions to load and attach to listener
     *
     * @var array
     */
    public $extensions = [];

    /**
     * Actions routes description map, indexed by action name.
     *
     * @var array
     */
    protected $_actions = [];

    /**
     * Actions classes map, indexed by action name.
     *
     * @var array
     */
    protected $_actionsClassMap = [];

    /**
     * Service url acceptable extensions list.
     *
     * @var array
     */
    protected $_routeExtensions = ['json'];

    /**
     *
     *
     * @var string
     */
    protected $_routePrefix = '';

    /**
     * Service name
     *
     * @var string
     */
    protected $_name = null;

    /**
     * Service version.
     *
     * @var int
     */
    protected $_version;

    /**
     * Parser class to process the HTTP request.
     *
     * @var \CakeDC\Api\Service\RequestParser\BaseParser
     */
    protected $_parser;

    /**
     * Renderer class to build the HTTP response.
     *
     * @var \CakeDC\Api\Service\Renderer\BaseRenderer
     */
    protected $_renderer;

    /**
     * The parser class.
     *
     * @var string
     */
    protected $_parserClass = null;

    /**
     * The Renderer class.
     *
     * @var string
     */
    protected $_rendererClass = null;

    /**
     * Dependent services names list
     *
     * @var array<string>
     */
    protected $_innerServices = [];

    /**
     * Parent service instance.
     *
     * @var \CakeDC\Api\Service\Service
     */
    protected $_parentService;

    /**
     * Service Action Result object.
     *
     * @var \CakeDC\Api\Service\Action\Result
     */
    protected $_result;

    /**
     * Base url for service.
     *
     * @var string
     */
    protected $_baseUrl;

    /**
     * Request
     *
     * @var \Cake\Http\ServerRequest
     */
    protected $_request;

    /**
     * Request
     *
     * @var \Cake\Http\Response
     */
    protected $_response;

    /**
     * @var string
     */
    protected $_corsSuffix = '_cors';

    /**
     * Extension registry.
     *
     * @var \CakeDC\Api\Service\ExtensionRegistry
     */
    protected $_extensions;

    /**
     * Action instance populated on prepare step.
     *
     * @var \CakeDC\Api\Service\Action\Action
     */
    protected $_action;

    /**
     * @return \CakeDC\Api\Service\Action\Action
     */
    public function getAction(): Action
    {
        return $this->_action;
    }

    /**
     * Service constructor.
     *
     * @param array $config Service configuration.
     * @throws \Exception
     */
    public function __construct(array $config = [])
    {
        if (isset($config['request'])) {
            $this->setRequest($config['request']);
        }
        if (isset($config['response'])) {
            $this->setResponse($config['response']);
        } else {
            $this->setResponse(new Response());
        }
        if (isset($config['baseUrl'])) {
            $this->_baseUrl = $config['baseUrl'];
        }
        if (isset($config['service'])) {
            $this->setName($config['service']);
        }
        if (isset($config['version'])) {
            $this->setVersion($config['version']);
        }
        if (isset($config['classMap'])) {
            $this->_actionsClassMap = Hash::merge($this->_actionsClassMap, $config['classMap']);
        }

        if (!empty($config['Extension'])) {
            $this->extensions = Hash::merge($this->extensions, $config['Extension']);
        }
        $extensionRegistry = $eventManager = null;
        if (!empty($config['eventManager'])) {
            $eventManager = $config['eventManager'];
        }
        $this->_eventManager = $eventManager ?: new EventManager();

        $this->initialize();
        $this->_initializeParser($config);
        $this->_initializeRenderer($config);
        $this->_eventManager->on($this);
        $this->setExtensions($extensionRegistry);
        $this->_loadExtensions();
    }

    /**
     * Initialize method
     *
     * @return void
     * @throws \ReflectionException
     */
    public function initialize(): void
    {
        if ($this->_name === null) {
            $className = (new \ReflectionClass($this))->getShortName();
            $this->setName(Inflector::underscore(str_replace('Service', '', $className)));
        }
    }

    /**
     * Gets service name.
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->_name;
    }

    /**
     * Sets service name.
     *
     * @param string $name Service name.
     * @return $this
     */
    public function setName(string $name)
    {
        $this->_name = $name;

        return $this;
    }

    /**
     * Gets service version number.
     *
     * @return int|null
     */
    public function getVersion(): ?int
    {
        return $this->_version;
    }

    /**
     * Sets service version.
     *
     * @param int $version Version number.
     * @return void
     */
    public function setVersion(int $version): void
    {
        $this->_version = $version;
    }

    /**
     * Gets the service parser.
     *
     * @return \CakeDC\Api\Service\RequestParser\BaseParser
     */
    public function getParser(): BaseParser
    {
        return $this->_parser;
    }

    /**
     * Sets the service parser.
     *
     * @param \CakeDC\Api\Service\RequestParser\BaseParser $parser A Parser instance.
     * @return $this
     */
    public function setParser(BaseParser $parser)
    {
        $this->_parser = $parser;

        return $this;
    }

    /**
     * Gets the Request.
     *
     * @return \Cake\Http\ServerRequest|null
     */
    public function getRequest(): ?ServerRequest
    {
        return $this->_request;
    }

    /**
     * Sets the Request.
     *
     * @param \Cake\Http\ServerRequest $request A Request object.
     * @return void
     */
    public function setRequest(ServerRequest $request): void
    {
        $this->_request = $request;
    }

    /**
     * Get the service route scopes and their connected routes.
     *
     * @return array
     */
    public function routes(): array
    {
        return $this->_routesWrapper(function () {
            return ApiRouter::routes();
        });
    }

    /**
     * @param callable $callable Wrapped router instance.
     * @return mixed
     */
    protected function _routesWrapper(callable $callable)
    {
        $this->resetRoutes();
        $this->loadRoutes();
        ApiRouter::$initialized = true;
        $result = $callable();
        $this->resetRoutes();

        return $result;
    }

    /**
     * Reset to default application routes.
     *
     * @return void
     */
    public function resetRoutes(): void
    {
        ApiRouter::reload();
    }

    /**
     * Initialize service level routes
     *
     * @return void
     */
    public function loadRoutes(): void
    {
        $defaultOptions = $this->routerDefaultOptions();
        ApiRouter::scope('/', $defaultOptions, function (RouteBuilder $routes) use ($defaultOptions) {
            if (is_array($this->_routeExtensions)) {
                $routes->setExtensions($this->_routeExtensions);
            }
            if (!empty($defaultOptions['map'])) {
                $routes->resources($this->getName(), $defaultOptions);
            }
        });
    }

    /**
     * Build router settings.
     * This implementation build action map for resource routes based on Service actions.
     *
     * @return array
     */
    public function routerDefaultOptions(): array
    {
        $mapList = [];
        foreach ($this->_actions as $alias => $map) {
            if (is_numeric($alias)) {
                $alias = $map;
                $map = [];
            }
            $mapCors = false;
            if (!empty($map['mapCors'])) {
                $mapCors = $map['mapCors'];
                unset($map['mapCors']);
            }
            $mapList[$alias] = $map;
            $mapList[$alias] += ['method' => 'GET', 'path' => '', 'action' => $alias];
            if ($mapCors) {
                $map['method'] = 'OPTIONS';
                $map += ['path' => '', 'action' => $alias . $this->_corsSuffix];
                $mapList[$alias . $this->_corsSuffix] = $map;
            }
        }

        return [
            'map' => $mapList,
        ];
    }

    /**
     * Finds URL for specified action.
     *
     * Returns an URL pointing to a combination of controller and action.
     *
     * @param string|array|null $route An array specifying any of the following:
     *   'controller', 'action', 'plugin' additionally, you can provide routed
     *   elements or query string parameters. If string it can be name any valid url
     *   string.
     * @return string Full translated URL with base path.
     * @throws \Cake\Core\Exception\Exception When the route name is not found
     */
    public function routeUrl($route): string
    {
        return $this->_routesWrapper(function () use ($route) {
            return ApiRouter::url($route);
        });
    }

    /**
     * Reverses a parsed parameter array into a string.
     *
     * @param \Cake\Http\ServerRequest|array $params The params array or
     *     Cake\Http\ServerRequest object that needs to be reversed.
     * @return string The string that is the reversed result of the array
     */
    public function routeReverse($params): ?string
    {
        return $this->_routesWrapper(function () use ($params) {
            try {
                return ApiRouter::reverse($params);
            } catch (Exception $e) {
                return null;
            }
        });
    }

    /**
     * Dispatch service call.
     *
     * @return \CakeDC\Api\Service\Action\Result
     */
    public function dispatch(): Result
    {
        try {
            $result = $this->_dispatch();

            if ($result instanceof Result) {
                $this->setResult($result);
            } else {
                $this->getResult()->setData($result);
                $this->getResult()->setCode(200);
            }
        } catch (RecordNotFoundException $e) {
            $this->getResult()->setCode(404);
            $this->getResult()->setException($e);
        } catch (ValidationException $e) {
            $this->getResult()->setCode(422);
            $this->getResult()->setException($e);
        } catch (Exception $e) {
            $code = $e->getCode();
            if (!is_int($code) || $code < 100 || $code >= 600) {
                $this->getResult()->setCode(500);
            }
            $this->getResult()->setException($e);
        }
        $this->dispatchEvent('Service.afterDispatch', ['service' => $this]);

        return $this->getResult();
    }

    /**
     * Dispatch service call.
     *
     * @return \CakeDC\Api\Service\Action\Result
     */
    public function dispatchPrepareAction(): ?Result
    {
        try {
            $result = $this->_prepareAction();

            if ($result instanceof Result) {
                $this->setResult($result);
            } else {
                return null;
            }
        } catch (RecordNotFoundException $e) {
            $this->getResult()->setCode(404);
            $this->getResult()->setException($e);
        } catch (ValidationException $e) {
            $this->getResult()->setCode(422);
            $this->getResult()->setException($e);
        } catch (Exception $e) {
            $code = $e->getCode();
            if (!is_int($code) || $code < 100 || $code >= 600) {
                $this->getResult()->setCode(500);
            }
            $this->getResult()->setException($e);
        }

        return $this->getResult();
    }

    /**
     * Dispatch service call.
     *
     * @param \Cake\Http\ServerRequest $request A Request object.
     * @return \CakeDC\Api\Service\Action\Result
     */
    public function dispatchProcessAction($request): Result
    {
        try {
            $this->setRequest($request);
            $result = $this->_processAction();

            if ($result instanceof Result) {
                $this->setResult($result);
            } else {
                $this->getResult()->setData($result);
                $this->getResult()->setCode(200);
            }
        } catch (RecordNotFoundException $e) {
            $this->getResult()->setCode(404);
            $this->getResult()->setException($e);
        } catch (ValidationException $e) {
            $this->getResult()->setCode(422);
            $this->getResult()->setException($e);
        } catch (Exception $e) {
            $code = $e->getCode();
            if (!is_int($code) || $code < 100 || $code >= 600) {
                $this->getResult()->setCode(500);
            }
            $this->getResult()->setException($e);
        }
        $this->dispatchEvent('Service.afterDispatch', ['service' => $this]);

        return $this->getResult();
    }

    /**
     * Dispatch service call through callbacks and action.
     *
     * @return \CakeDC\Api\Service\Action\Result|null

     */
    protected function _dispatch()
    {
        $this->_prepareAction();

        return $this->_processAction();
    }

    /**
     * Prepare action for processing.
     *
     * @return \CakeDC\Api\Service\Action\Result|null
     */
    protected function _prepareAction()
    {
        $event = $this->dispatchEvent('Service.beforeDispatch', ['service' => $this]);
        if ($event->getResult() instanceof Result) {
            return $event->getResult();
        }

        $this->_action = $this->buildAction();

        return null;
    }

    /**
     * Execute action.
     *
     * @return \CakeDC\Api\Service\Action\Result|null
     */
    protected function _processAction()
    {
        $response = $this->dispatchEvent('Service.beforeProcess', ['service' => $this, 'action' => $this]);
        if ($response->getResult() instanceof Result) {
            return $response->getResult();
        }

        return $this->getAction()->process();
    }

    /**
     * Build action instance
     *
     * @return \CakeDC\Api\Service\Action\Action
     * @throws \Exception
     */
    public function buildAction(): Action
    {
        $route = $this->parseRoute($this->getBaseUrl());
        if (empty($route)) {
            throw new MissingActionException('Invalid Action Route:' . $this->getBaseUrl()); // InvalidActionException
        }
        $service = null;
        $serviceName = Inflector::underscore($route['controller']);
        if ($serviceName == $this->getName()) {
            $service = $this;
        }
        if (in_array($serviceName, $this->_innerServices)) {
            $options = [
                'version' => $this->getVersion(),
                'request' => $this->getRequest(),
                'response' => $this->getResponse(),
                'refresh' => true,
            ];
            $service = ServiceRegistry::getServiceLocator()->get($serviceName, $options);
            $service->setParentService($this);
        }
        $action = $route['action'];
        [$namespace, $serviceClass] = namespaceSplit(get_class($service));
        $actionPrefix = substr($serviceClass, 0, -7);
        $actionClass = $namespace . '\\Action\\' . $actionPrefix . Inflector::camelize($action) . 'Action';
        if (class_exists($actionClass)) {
            return $service->buildActionClass($actionClass, $route);
        }
        $actionsClassMap = $service->getActionsClassMap();
        if (array_key_exists($action, $actionsClassMap)) {
            return $service->buildActionClass($actionsClassMap[$action], $route);
        }
        throw new MissingActionException(['class' => $actionClass]);
    }

    /**
     * Parses given URL string. Returns 'routing' parameters for that URL.
     *
     * @param string $url URL to be parsed
     * @return array Parsed elements from URL
     * @throws \Cake\Routing\Exception\MissingRouteException When a route cannot be handled
     */
    public function parseRoute(string $url): array
    {
        return $this->_routesWrapper(function () use ($url) {
            return ApiRouter::parseRequest(new ServerRequest([
                'url' => $url,
                'environment' => [
                    'REQUEST_METHOD' => $this->_request->getEnv('REQUEST_METHOD'),
                ],
            ]));
        });
    }

    /**
     * Returns action class map.
     *
     * @return array
     */
    public function getActionsClassMap(): array
    {
        return $this->_actionsClassMap;
    }

    /**
     * Build base url
     *
     * @return string
     */
    public function getBaseUrl(): string
    {
        if (!empty($this->_baseUrl)) {
            return $this->_baseUrl;
        }

        $result = '/' . $this->getName();

        return $result;
    }

    /**
     * Gets the parent service method.
     *
     * @return self|null
     */
    public function getParentService()
    {
        return $this->_parentService;
    }

    /**
     * Sets the parent service method.
     *
     * @param \CakeDC\Api\Service\Service $parentService Parent Service
     * @return $this
     */
    public function setParentService(Service $parentService)
    {
        $this->_parentService = $parentService;

        return $this;
    }

    /**
     * Build action class
     *
     * @param string $class Class name.
     * @param array $route Activated route.
     * @return mixed
     */
    public function buildActionClass(string $class, array $route)
    {
        $instance = new $class($this->_actionOptions($route));

        return $instance;
    }

    /**
     * Action constructor options.
     *
     * @param array $route Activated route.
     * @return array
     */
    protected function _actionOptions(array $route): array
    {
        $actionName = $route['action'];

        $options = [
            'name' => $actionName,
            'service' => $this,
            'route' => $route,
        ];
        $options += (new ConfigReader())->actionOptions($this->getName(), $actionName, $this->getVersion());

        return $options;
    }

    /**
     * Gets the result for service.
     *
     * @return \CakeDC\Api\Service\Action\Result
     */
    public function getResult(): Result
    {
        if ($this->_parentService !== null) {
            return $this->_parentService->getResult();
        }
        if ($this->_result === null) {
            $this->_result = new Result();
        }

        return $this->_result;
    }

    /**
     * Sets the result for service.
     *
     * @param \CakeDC\Api\Service\Action\Result $result A Result object.
     * @return $this
     */
    public function setResult(Result $result)
    {
        if ($this->_parentService !== null) {
            $this->_parentService->setResult($result);

            return $this;
        }
        $this->_result = $result;

        return $this;
    }

    /**
     *  Fill up response and stop execution.
     *
     * @param \CakeDC\Api\Service\Action\Result $result A Result instance.
     *
     * @return \Cake\Http\Response|null
     */
    public function respond(?Result $result = null): ?Response
    {
        if ($result === null) {
            $result = $this->getResult();
        }
        $this->setResponse($this->getResponse()->withStatus($result->getCode()));
        if ($result->getException() !== null) {
            $this->getRenderer()
                 ->error($result->getException());
        } else {
            $this->getRenderer()
                 ->response($result);
        }

        return $this->getResponse();
    }

    /**
     * Gets the response.
     *
     * @return \Cake\Http\Response|null
     */
    public function getResponse(): ?Response
    {
        return $this->_response;
    }

    /**
     * Sets the response.
     *
     * @param \Cake\Http\Response $response Response
     * @return $this
     */
    public function setResponse(Response $response)
    {
        $this->_response = $response;

        return $this;
    }

    /**
     * Gets the service renderer.
     *
     * @return \CakeDC\Api\Service\Renderer\BaseRenderer
     */
    public function getRenderer(): BaseRenderer
    {
        return $this->_renderer;
    }

    /**
     * Sets the service renderer.
     *
     * @param \CakeDC\Api\Service\Renderer\BaseRenderer $renderer Rendered
     * @return $this
     */
    public function setRenderer(BaseRenderer $renderer)
    {
        $this->_renderer = $renderer;

        return $this;
    }

    /**
     * Define action config.
     *
     * @param string $actionName Action name.
     * @param string $className Class name.
     * @param array $route Route config.
     * @return void
     */
    public function mapAction(string $actionName, string $className, array $route): void
    {
        $route += ['mapCors' => false];
        $this->_actionsClassMap[$actionName] = $className;
        if ($route['mapCors']) {
            $this->_actionsClassMap[$actionName . $this->_corsSuffix] = DummyAction::class;
        }
        if (!isset($route['path'])) {
            $route['path'] = $actionName;
        }
        $this->_actions[$actionName] = $route;
    }

    /**
     * Lists supported events.
     *
     * @return array
     */
    public function implementedEvents(): array
    {
        $eventMap = [
            'Service.beforeDispatch' => 'beforeDispatch',
            'Service.beforeProcess' => 'beforeProcess',
            'Service.afterDispatch' => 'afterDispatch',
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
     * Gets the extension registry instance.
     *
     * @return \CakeDC\Api\Service\ExtensionRegistry
     */
    public function getExtensions(): ExtensionRegistry
    {
        if ($this->_extensions === null) {
            $this->_extensions = new ExtensionRegistry($this);
        }

        return $this->_extensions;
    }

    /**
     * Sets the extension registry for this service.
     *
     * @param \CakeDC\Api\Service\ExtensionRegistry|null $extensions The extension registry instance.
     * @return self
     */
    public function setExtensions(?ExtensionRegistry $extensions): self
    {
        if ($extensions === null) {
            $extensions = new ExtensionRegistry($this);
        }
        $this->_extensions = $extensions;

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
        $extensions = $registry->normalizeArray($this->extensions);
        foreach ($extensions as $properties) {
            $instance = $registry->load($properties['class'], $properties['config']);
            $this->_eventManager->on($instance);
        }
    }

    /**
     * Initialize parser.
     *
     * @param array $config Service options
     * @return void
     */
    protected function _initializeParser(array $config): void
    {
        if (empty($this->_parserClass) && isset($config['parserClass'])) {
            $this->_parserClass = $config['parserClass'];
        }
        $parserClass = Configure::read('Api.parser');
        if (empty($this->_parserClass) && !empty($parserClass)) {
            $this->_parserClass = $parserClass;
        }

        $class = App::className($this->_parserClass, 'Service/RequestParser', 'Parser');
        if ($class === null || !class_exists($class)) {
            throw new MissingParserException(['class' => $this->_parserClass]);
        }
        $this->_parser = new $class($this);
    }

    /**
     * Initialize renderer.
     *
     * @param array $config Service options.
     * @return void
     */
    protected function _initializeRenderer(array $config): void
    {
        if (empty($this->_rendererClass) && isset($config['rendererClass'])) {
            $this->_rendererClass = $config['rendererClass'];
        }
        $rendererClass = Configure::read('Api.renderer');
        if (empty($this->_rendererClass) && !empty($rendererClass)) {
            $this->_rendererClass = $rendererClass;
        }

        $class = App::className($this->_rendererClass, 'Service/Renderer', 'Renderer');
        if (!class_exists($class)) {
            throw new MissingRendererException(['class' => $this->_rendererClass]);
        }
        $this->setRenderer(new $class($this));
    }
}
