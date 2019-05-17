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

namespace CakeDC\Api\Service\Utility;

use Cake\Core\Configure;
use Cake\Routing\Router;
use Cake\Utility\Inflector;
use CakeDC\Api\Service\Action\Action;

/**
 * Class ReverseRouting
 *
 * @package CakeDC\Api\Service\Response
 */
class ReverseRouting
{
    /**
     * Builds link to action.
     *
     * @param string $name Link name
     * @param string|null $path Link path.
     * @param string $method Action method.
     * @return array
     */
    public function link(string $name, ?string $path, string $method = 'GET'): array
    {
        $prefix = Configure::read('Api.routeBase') ?: '/api';
        $baseRoute = $prefix . $path;
        $fullRoute = Router::url($baseRoute, true);

        return [
            'name' => $name,
            'href' => $fullRoute,
            'rel' => $baseRoute,
            'method' => $method,
        ];
    }

    /**
     * Builds path to the index action.
     *
     * @param \CakeDC\Api\Service\Action\Action $action An Action instance.
     * @param callable $beforeReverse Callback.
     * @return array|string
     */
    public function indexPath(Action $action, ?callable $beforeReverse = null): ?string
    {
        $indexRoute = $action->getRoute();
        $parent = $action->getService()->getParentService();
        $path = null;
        if ($parent !== null) {
            $parentRoutes = $parent->routes();
            $currentRoute = $this->findRoute($indexRoute, $parentRoutes);
            if ($currentRoute !== null) {
                if (is_callable($beforeReverse)) {
                    $indexRoute = $beforeReverse($indexRoute);
                }

                return $parent->routeReverse($indexRoute);
            }

            return $path;
        } else {
            if (is_callable($beforeReverse)) {
                $indexRoute = $beforeReverse($indexRoute);
            }

            return $action->getService()->routeReverse($indexRoute);
        }
    }

    /**
     * Builds path to the parent view action.
     *
     * @param string $parentName Action name.
     * @param \CakeDC\Api\Service\Action\Action $action An Action instance.
     * @param string $type Type of action.
     * @return string|null
     */
    public function parentViewPath(string $parentName, Action $action, string $type): ?string
    {
        $baseRoute = $action->getRoute();
        $parent = $action->getService()->getParentService();
        $parentId = Inflector::singularize(Inflector::underscore($parent->getName())) . '_id';
        $route = collection($parent->routes())
            ->filter(function ($item) use ($parentName) {
                return $item->getName() == $parentName;
            })
            ->first();
        $routeDefault = $route->defaults;
        if (array_key_exists($parentId, $baseRoute)) {
            if ($type == 'view') {
                $routeDefault['pass']['id'] = $baseRoute[$parentId];
            }
            if ($type == 'index') {
                $routeDefault[$parentId] = $baseRoute[$parentId];
            }
        }

        return $parent->routeReverse($routeDefault);
    }

    /**
     * Extract matching route from routes list.
     *
     * @param array $route Route array.
     * @param array $routes List of all routes.
     * @return null
     */
    public function findRoute(array $route, array $routes)
    {
        foreach ($routes as $item) {
            if ($this->compareDefaults($item->defaults, $route)) {
                return $item;
            }
        }

        return null;
    }

    /**
     * Compares two routes.
     *
     * @param array $route1 First route description instance.
     * @param array $route2 Second route description instance.
     * @return bool
     */
    public function compareDefaults(array $route1, array $route2): bool
    {
        $result = true;
        $fields = ['controller', 'action', 'plugin'];
        foreach ($fields as $field) {
            $result = $result && $route1[$field] === $route2[$field];
        }
        $result = $result && (
                is_string($route1['_method']) && $route1['_method'] === $route2['_method'] ||
                is_array($route1['_method']) && in_array($route2['_method'], $route1['_method'])
            );

        return $result;
    }
}
