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

namespace CakeDC\Api\Service\Action\Extension;

use CakeDC\Api\Service\Action\Action;
use CakeDC\Api\Service\Action\CrudAction;
use CakeDC\Api\Service\Action\ExtensionRegistry;
use CakeDC\Api\Service\Utility\ReverseRouting;
use Cake\Event\Event;
use Cake\Event\EventListenerInterface;
use Cake\Utility\Inflector;

/**
 * Class CrudHateoasExtension
 *
 * @package CakeDC\Api\Service\Action\Extension
 */
class CrudHateoasExtension extends Extension implements EventListenerInterface
{

    /**
     * @var ReverseRouting
     */
    protected $_reverseRouter;

    /**
     * CrudHateous Extension constructor.
     *
     * @param ExtensionRegistry $registry An ExtensionRegistry instance.
     * @param array $config Configuration.
     */
    public function __construct(ExtensionRegistry $registry, array $config = [])
    {
        parent::__construct($registry, $config);
        $this->_reverseRouter = new ReverseRouting();
    }

    /**
     * Returns a list of events this object is implementing. When the class is registered
     * in an event manager, each individual method will be associated with the respective event.
     *
     * @return array
     */
    public function implementedEvents()
    {
        return [
            'Action.afterProcess' => 'afterAction',
        ];
    }

    /**
     * After action callback.
     *
     * @param Event $event An Event instance.
     * @return void
     */
    public function afterAction(Event $event)
    {
        $action = $event->subject();
        $result = $action->service()->result();
        $actionName = $action->name();
        $links = [];
        //$route = $action->route();
        if ($actionName == 'view') {
            $links = $this->_buildViewLinks($action);
        }
        if ($actionName == 'index') {
            $links = $this->_buildIndexLinks($action);
        }

        $parent = $action->service()->parent();

        if ($parent !== null) {
            $result = $parent->result();
        }
        $result->setPayload('links', $links);
    }

    /**
     * Builds index action links.
     *
     * @param Action $action An Action instance.
     * @return array
     */
    protected function _buildIndexLinks(Action $action)
    {
        $links = [];
        $indexRoute = $action->route();
        $parent = $action->service()->parent();
        $path = $this->_reverseRouter->indexPath($action);

        $links[] = $this->_reverseRouter->link('self', $path, $indexRoute['_method']);
        $links[] = $this->_reverseRouter->link($action->service()->name() . ':add', $path, 'POST');

        if ($parent !== null) {
            $parentName = $parent->name() . ':view';
            $path = $this->_reverseRouter->parentViewPath($parentName, $action, 'view');
            $links[] = $this->_reverseRouter->link($parentName, $path, 'GET');
        }

        return $links;
    }

    /**
     * Builds view action links.
     *
     * @param Action $action An Action instance.
     * @return array
     */
    protected function _buildViewLinks(Action $action)
    {
        $links = [];
        $viewRoute = $action->route();
        $service = $action->service();
        $parent = $action->service()->parent();
        $path = null;
        if ($parent !== null) {
            $parentRoutes = $parent->routes();
            $currentRoute = $this->_reverseRouter->findRoute($viewRoute, $parentRoutes);
            if ($currentRoute !== null) {
                unset($viewRoute['id']);
                $path = $parent->routeReverse($viewRoute);
                array_pop($viewRoute['pass']);

                $indexName = $service->name() . ':index';
                $indexPath = $this->_reverseRouter->parentViewPath($indexName, $action, 'index');
            }
        } else {
            unset($viewRoute['id']);
            $path = $service->routeReverse($viewRoute);
            array_pop($viewRoute['pass']);

            $indexName = $service->name() . ':index';
            $route = collection($service->routes())
                ->filter(function ($item) use ($indexName) {
                    return $item->getName() == $indexName;
                })
                ->first();
            $indexPath = $service->routeReverse($route->defaults);
        }

        $links[] = $this->_reverseRouter->link('self', $path, $viewRoute['_method']);
        $links[] = $this->_reverseRouter->link($action->service()->name() . ':edit', $path, 'PUT');
        $links[] = $this->_reverseRouter->link($action->service()->name() . ':delete', $path, 'DELETE');
        if (!empty($indexPath)) {
            $links[] = $this->_reverseRouter->link($action->service()->name() . ':index', $indexPath, 'GET');
        }

        if ($parent === null && $action instanceof CrudAction) {
            $table = $action->table();
            $hasMany = $table->associations()->type('HasMany');
            foreach ($hasMany as $assoc) {
                $target = $assoc->target();
                $alias = $target->alias();

                $targetClass = get_class($target);
                list(, $className) = namespaceSplit($targetClass);
                $className = preg_replace('/(.*)Table$/', '\1', $className);
                if ($className === '') {
                    $className = $alias;
                }
                $serviceName = Inflector::underscore($className);

                $indexName = $serviceName . ':index';
                $route = collection($service->routes())
                    ->filter(function ($item) use ($indexName) {
                        return $item->getName() == $indexName;
                    })
                    ->first();

                $currentId = Inflector::singularize(Inflector::underscore($service->name())) . '_id';
                $defaults = $route->defaults;
                $viewRoute = $action->route();
                $defaults[$currentId] = $viewRoute['id'];
                $indexPath = $service->routeReverse($defaults);

                $links[] = $this->_reverseRouter->link($serviceName . ':index', $indexPath, 'GET');
            }
        }

        if ($parent !== null) {
            $parentName = $parent->name() . ':view';
            $path = $this->_reverseRouter->parentViewPath($parentName, $action, 'view');
            $links[] = $this->_reverseRouter->link($parentName, $path, 'GET');
        }

        return $links;
    }
}
