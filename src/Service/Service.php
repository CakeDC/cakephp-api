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

namespace CakeDC\Api\Service;

use CakeDC\Api\Exception\ValidationException;
use CakeDC\Api\Routing\ApiRouter;
use CakeDC\Api\Service\Action\DummyAction;
use CakeDC\Api\Service\Action\Result;
use CakeDC\Api\Service\Exception\MissingActionException;
use CakeDC\Api\Service\Exception\MissingParserException;
use CakeDC\Api\Service\Exception\MissingRendererException;
use CakeDC\Api\Service\Renderer\BaseRenderer;
use CakeDC\Api\Service\RequestParser\BaseParser;

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
     * @var BaseParser
     */
    protected $_parser;

    /**
     * Renderer class to build the HTTP response.
     *
     * @var BaseRenderer
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
     * @var Service
     */
    protected $_parentService;

    /**
     * Service Action Result object.
     *
     * @var Result
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

    protected $classPrefix = '';

    /**
     * Action instance populated on prepare step.
     *
     * @var \CakeDC\Api\Service\Action\Action
     */
    protected $_action;

    /**
     * @return \CakeDC\Api\Service\Action\Action
     */
    public function getAction()
    {
        return $this->_action;
    }

    /**
     * Service constructor.
     *
     * @param array $config Service configuration.
     */
    public function __construct(array $config = [])
    {
        if (isset($config['request'])) {
            $this->setRequest($config['request']);
        }
        if (isset($config['classPrefix'])) {
            $this->classPrefix = $config['classPrefix'];
        }
        if (isset($config['response'])) {
            $this->setResponse($config['response']);
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
            $this->extensions = (Hash::merge($this->extensions, $config['Extension']));
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
     */
    public function initialize()
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
    public function getName()
    {
        return $this->_name;
    }

    /**
     * Sets service name.
     *
     * @param string $name Service name.
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
     * @param string $name Service name.
     * @deprecated 3.4.0 Use setName()/getName() instead.
     * @return string
     */
    public function name($name = null)
    {
        deprecationWarning(
            'Service::name() is deprecated. ' .
            'Use Service::setName()/getName() instead.'
        );

        if ($name !== null) {
            return $this->setName($name);
        }

        return $this->getName();
    }

    /**
     * Gets service version number.
     *
     * @return int
     */
    public function getVersion()
    {
        return $this->_version;
    }

    /**
     * Sets service version.
     *
     * @param int $version Version number.
     * @return void
     */
    public function setVersion($version)
    {
        $this->_version = $version;
    }

    /**
     * Get and set service version.
     *
     * @param int $version Version number.
     * @deprecated 3.4.0 Use setVersion()/getVersion() instead.
     * @return int|$this
     */
    public function version($version = null)
    {
        deprecationWarning(
            'Service::version() is deprecated. ' .
            'Use Service::setVersion()/getVersion() instead.'
        );

        if ($version !== null) {
            return $this->setVersion($version);
        }

        return $this->getVersion();
    }

    /**
     * Gets the service parser.
     *
     * @return BaseParser
     */
    public function getParser()
    {
        return $this->_parser;
    }

    /**
     * Sets the service parser.
     *
     * @param BaseParser $parser A Parser instance.
     * @return $this
     */
    public function setParser(BaseParser $parser)
    {
        $this->_parser = $parser;

        return $this;
    }

    /**
     * Service parser configuration method.
     *
     * @param BaseParser $parser A Parser instance.
     * @deprecated 3.4.0 Use getParser()/setParser() instead.
     * @return BaseParser|$this
     */
    public function parser(BaseParser $parser = null)
    {
        deprecationWarning(
            'Service::parser() is deprecated. ' .
            'Use Service::setParser()/getParser() instead.'
        );

        if ($parser !== null) {
            return $this->setParser($parser);
        }

        return $this->getParser();
    }

    /**
     * Gets the Request.
     *
     * @return \Cake\Http\ServerRequest
     */
    public function getRequest()
    {
        return $this->_request;
    }

    /**
     * Sets the Request.
     *
     * @param \Cake\Http\ServerRequest $request A Request object.
     * @return void
     */
    public function setRequest(ServerRequest $request)
    {
        $this->_request = $request;
    }

    /**
     * Get and set request.
     *
     * @param \Cake\Http\ServerRequest $request A Request object.
     * @deprecated 3.4.0 Use getRequest()/setRequest() instead.
     * @return \Cake\Http\ServerRequest|$this
     */
    public function request($request = null)
    {
        deprecationWarning(
            'Service::request() is deprecated. ' .
            'Use Service::setRequest()/getRequest() instead.'
        );

        if ($request !== null) {
            return $this->setRequest($request);
        }

        return $this->getRequest();
    }

    /**
     * Get the service route scopes and their connected routes.
     *
     * @return array
     */
    public function routes()
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
    public function resetRoutes()
    {
        ApiRouter::reload();
    }

    /**
     * Initialize service level routes
     *
     * @return void
     */
    public function loadRoutes()
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
    public function routerDefaultOptions()
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
            'map' => $mapList
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
    public function routeUrl($route)
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
    public function routeReverse($params)
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
    public function dispatch()
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
    public function dispatchPrepareAction()
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
    public function dispatchProcessAction($request)
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
     * @return \CakeDC\Api\Service\Action\Result

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
        if ($event->result instanceof Result) {
            return $event->result;
        }

        $this->_action = $this->buildAction();
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
     * @throws Exception
     */
    public function buildAction()
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
            if ($this->classPrefix !== '') {
                $options['classPrefix'] = $this->classPrefix;
            }
            $service = ServiceRegistry::getServiceLocator()->get($serviceName, $options);
            $service->setParentService($this);
        }
        $action = $route['action'];
        list($namespace, $serviceClass) = namespaceSplit(get_class($service));
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
    public function parseRoute($url)
    {
        return $this->_routesWrapper(function () use ($url) {
            return ApiRouter::parseRequest(new ServerRequest([
                'url' => $url,
                'environment' => [
                    'REQUEST_METHOD' => $this->_request->getEnv('REQUEST_METHOD')
                ]
            ]));
        });
    }

    /**
     * Returns action class map.
     *
     * @return array
     */
    public function getActionsClassMap()
    {
        return $this->_actionsClassMap;
    }

    /**
     * Build base url
     *
     * @return string
     */
    public function getBaseUrl()
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
     * @return Service
     */
    public function getParentService()
    {
        return $this->_parentService;
    }

    /**
     * Sets the parent service method.
     *
     * @param Service $parentService Parent Service
     * @return $this
     */
    public function setParentService(Service $parentService)
    {
        $this->_parentService = $parentService;

        return $this;
    }

    /**
     * Parent service get and set methods.
     *
     * @param Service $service Parent Service instance.
     * @deprecated 3.4.0 Use getParentService()/setParentService() instead.
     * @return Service|$this
     */
    public function parent(Service $service = null)
    {
        deprecationWarning(
            'Service::parent() is deprecated. ' .
            'Use Service::setParentService()/getParentService() instead.'
        );

        if ($service !== null) {
            return $this->setParentService($service);
        }

        return $this->getParentService();
    }

    /**
     * Build action class
     *
     * @param string $class Class name.
     * @param array $route Activated route.
     * @return mixed
     */
    public function buildActionClass($class, $route)
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
    protected function _actionOptions($route)
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
     * @return Result
     */
    public function getResult()
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
     * @param Result $result A Result object.
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
     * @param null $value value
     * @deprecated 3.4.0 Use getResult()/setResult() instead.
     * @return Result
     */
    public function result($value = null)
    {
        deprecationWarning(
            'Service::result() is deprecated. ' .
            'Use Service::setResult()/getResult() instead.'
        );

        if ($value !== null) {
            return $this->setResult($value);
        }

        return $this->getResult();
    }

    /**
     * Fill up response and stop execution.
     *
     * @param Result $result A Result instance.
     * @return Response
     */
    public function respond($result = null)
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
     * @return \Cake\Http\Response
     */
    public function getResponse()
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
     * Get and set response.
     *
     * @param \Cake\Http\Response $response  A Response object.
     * @deprecated 3.4.0 Use getResponse()/setResponse() instead.
     * @return \Cake\Http\Response
     */
    public function response(Response $response = null)
    {
        deprecationWarning(
            'Service::response() is deprecated. ' .
            'Use Service::setResponse()/getResponse() instead.'
        );

        if ($response !== null) {
            return $this->setResponse($response);
        }

        return $this->getResponse();
    }

    /**
     * Gets the service renderer.
     *
     * @return BaseRenderer
     */
    public function getRenderer()
    {
        return $this->_renderer;
    }

    /**
     * Sets the service renderer.
     *
     * @param BaseRenderer $renderer Rendered
     * @return $this
     */
    public function setRenderer(BaseRenderer $renderer)
    {
        $this->_renderer = $renderer;

        return $this;
    }

    /**
     * Service renderer configuration method.
     *
     * @param BaseRenderer $renderer A Renderer instance.
     * @deprecated 3.4.0 Use getRenderer()/setRenderer() instead.
     * @return BaseRenderer|$this
     */
    public function renderer(BaseRenderer $renderer = null)
    {
        deprecationWarning(
            'Service::renderer() is deprecated. ' .
            'Use Service::setRenderer()/getRenderer() instead.'
        );

        if ($renderer !== null) {
            return $this->setRenderer($renderer);
        }

        return $this->getRenderer();
    }

    /**
     * Define action config.
     *
     * @param string $actionName Action name.
     * @param string $className Class name.
     * @param array $route Route config.
     * @return void
     */
    public function mapAction($actionName, $className, $route)
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
    public function implementedEvents()
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
    public function getExtensions()
    {
        if ($this->_extensions === null) {
            $this->_extensions = new ExtensionRegistry($this);
        }

        return $this->_extensions;
    }

    /**
     * Sets the extension registry for this service.
     *
     * @param \CakeDC\Api\Service\ExtensionRegistry $extensions The extension registry instance.
     * @return $this
     */
    public function setExtensions($extensions)
    {
        if ($extensions === null) {
            $extensions = new ExtensionRegistry($this);
        }
        $this->_extensions = $extensions;

        return $this;
    }

    /**
     * Get the extension registry for this service.
     *
     * If called with the first parameter, it will be set as the action $this->_extensions property
     *
     * @param \CakeDC\Api\Service\ExtensionRegistry|null $extensions Extension registry.
     * @deprecated 3.4.0 Use getExtensions()/setExtensions() instead.
     * @return \CakeDC\Api\Service\ExtensionRegistry|$this
     */
    public function extensions($extensions = null)
    {
        deprecationWarning(
            'Service::extensions() is deprecated. ' .
            'Use Service::setExtensions()/getExtensions() instead.'
        );

        if ($extensions !== null) {
            $this->setExtensions($extensions);
        }

        return $this->getExtensions();
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
    protected function _initializeParser(array $config)
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
    protected function _initializeRenderer(array $config)
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
