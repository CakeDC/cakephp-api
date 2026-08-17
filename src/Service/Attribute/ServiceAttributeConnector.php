<?php
declare(strict_types=1);

/**
 * Copyright 2016 - 2026, Cake Development Corporation (http://cakedc.com)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright 2016 - 2026, Cake Development Corporation (http://cakedc.com)
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

namespace CakeDC\Api\Service\Attribute;

use Cake\AttributeResolver\AttributeResolver;
use Cake\AttributeResolver\Enum\AttributeTargetType;
use CakeDC\Api\Service\Service;

/**
 * Applies service routing attributes to a service instance.
 *
 * The `ApiScope` / `ApiResource` / `ApiActions` attributes are read from the
 * service class; each action class listed in `ApiActions` carries its own route
 * via `ApiRoute` / HTTP shortcut attributes. The connector turns those into
 * `Service::mapAction()` registrations.
 *
 * Invoked automatically from `Service::initialize()`; a no-op unless the attribute
 * resolver is configured. Discovery follows the CakePHP 6 model: the resolver
 * scans once and the whole attribute collection is cached (in memory and,
 * optionally, on disk via PhpEngine); the connector only filters that collection.
 */
class ServiceAttributeConnector
{
    /**
     * Routing attributes recognized by the connector.
     *
     * @var list<class-string>
     */
    protected const array SUPPORTED_ATTRIBUTES = [
        ApiActions::class,
        ApiRoute::class,
        ApiGet::class,
        ApiPost::class,
        ApiPut::class,
        ApiPatch::class,
        ApiDelete::class,
        ApiOptions::class,
        ApiScope::class,
        ApiResource::class,
    ];

    /**
     * Apply routing attributes declared on the service class to the given instance.
     *
     * @param \CakeDC\Api\Service\Service $service Service instance.
     * @return void
     */
    public function apply(Service $service): void
    {
        if (AttributeResolver::getConfig('default') === null) {
            return;
        }

        $className = $service::class;
        $hierarchy = array_reverse(array_values(class_parents($className)));
        $hierarchy[] = $className;

        $collection = AttributeResolver::collection('default')
            ->withAttribute(static::SUPPORTED_ATTRIBUTES);

        $serviceInfos = $collection->withClassName($hierarchy)->toList();

        $state = $this->buildClassState($hierarchy, $serviceInfos);
        if ($state['resource'] !== null) {
            $service->setResourceOptions($state['resource']);
        }

        foreach ($this->actionClasses($serviceInfos) as $actionClass) {
            foreach ($this->routeAttributes($collection->withClassName($actionClass)->toList()) as $entry) {
                $this->connectRoute($service, $state, $actionClass, $entry['action'], $entry['route']);
            }
        }
    }

    /**
     * Aggregate class-level service state across the class hierarchy.
     *
     * @param list<string> $hierarchy Parent-to-child class names.
     * @param list<\Cake\AttributeResolver\ValueObject\AttributeInfo> $infos Attribute metadata.
     * @return array{scopePath: string, scopeDefaults: array<string, mixed>, scopePatterns: array<string, string>, resource: array<string, mixed>|null}
     */
    protected function buildClassState(array $hierarchy, array $infos): array
    {
        $byClass = [];
        foreach ($infos as $info) {
            if ($info->target->type === AttributeTargetType::CLASS_) {
                $byClass[$info->className][] = $info;
            }
        }

        $state = [
            'scopePath' => '',
            'scopeDefaults' => [],
            'scopePatterns' => [],
            'resource' => null,
        ];

        foreach ($hierarchy as $serviceClass) {
            foreach ($byClass[$serviceClass] ?? [] as $info) {
                $instance = $info->getInstance();
                if ($instance instanceof ApiScope) {
                    $state['scopePath'] .= $instance->path;
                    $state['scopeDefaults'] = array_merge($state['scopeDefaults'], $instance->defaults);
                    $state['scopePatterns'] = array_merge($state['scopePatterns'], $instance->patterns);

                    continue;
                }
                if ($instance instanceof ApiResource) {
                    $state['resource'] = [
                        'path' => $instance->path,
                        'only' => $instance->only,
                        'actions' => $instance->actions,
                        'map' => $instance->map,
                        'id' => $instance->id,
                    ];
                }
            }
        }

        return $state;
    }

    /**
     * Collect action classes listed by `ApiActions` attributes across the hierarchy.
     *
     * @param list<\Cake\AttributeResolver\ValueObject\AttributeInfo> $infos Attribute metadata.
     * @return list<string>
     */
    protected function actionClasses(array $infos): array
    {
        $classes = [];
        foreach ($infos as $info) {
            if ($info->target->type !== AttributeTargetType::CLASS_) {
                continue;
            }
            if (!$info->isInstanceOf(ApiActions::class)) {
                continue;
            }
            $instance = $info->getInstance();
            foreach ((array)$instance->actions as $actionClass) {
                $classes[] = $actionClass;
            }
        }

        return $classes;
    }

    /**
     * Collect route attributes declared on an action class.
     *
     * @param list<\Cake\AttributeResolver\ValueObject\AttributeInfo> $infos Attribute metadata.
     * @return list<array{action: string, route: \CakeDC\Api\Service\Attribute\ApiRoute}>
     */
    protected function routeAttributes(array $infos): array
    {
        $routes = [];
        foreach ($infos as $info) {
            if ($info->target->type !== AttributeTargetType::CLASS_) {
                continue;
            }
            if (!$info->isInstanceOf(ApiRoute::class)) {
                continue;
            }
            $route = $info->getInstance();
            $routes[] = [
                'action' => $route->action,
                'route' => $route,
            ];
        }

        return $routes;
    }

    /**
     * Connect a route attribute into the service actions map.
     *
     * @param \CakeDC\Api\Service\Service $service Service instance.
     * @param array{scopePath: string, scopeDefaults: array<string, mixed>, scopePatterns: array<string, string>, resource: array<string, mixed>|null} $state Service state.
     * @param string $actionClass Action class name.
     * @param string $actionName Action name.
     * @param \CakeDC\Api\Service\Attribute\ApiRoute $route Route attribute.
     * @return void
     */
    protected function connectRoute(Service $service, array $state, string $actionClass, string $actionName, ApiRoute $route): void
    {
        $path = trim($state['scopePath'], '/') . '/' . trim($route->path, '/');
        $path = trim($path, '/');

        $options = [
            'method' => (array)$route->method,
            'mapCors' => $route->mapCors,
        ] + $route->defaults;

        if ($path !== '') {
            $options['path'] = $path;
        }

        if ($route->patterns !== []) {
            $options['patterns'] = array_merge($state['scopePatterns'], $route->patterns);
        } elseif ($state['scopePatterns'] !== []) {
            $options['patterns'] = $state['scopePatterns'];
        }
        if ($route->pass !== null) {
            $options['pass'] = $route->pass;
        }
        if ($route->name !== null) {
            $options['name'] = $route->name;
        }

        $service->mapAction($actionName, $actionClass, $options);
    }
}
