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

use Cake\ORM\Locator\LocatorAwareTrait;
use Cake\Routing\RouteBuilder;
use Cake\Utility\Inflector;
use CakeDC\Api\Routing\ApiRouter;

/**
 * Class FallbackService
 *
 * @package CakeDC\Api\Service
 */
class FallbackService extends NestedCrudService
{
    use LocatorAwareTrait;

    /**
     * Table name.
     *
     * @var string
     */
    protected $_table = null;

    /**
     * Initialize method
     *
     * @return void
     */
    public function initialize(): void
    {
        parent::initialize();
        if (empty($this->_table)) {
            $this->_table = Inflector::pluralize(Inflector::camelize($this->getName()));
        }
    }

    /**
     * Initialize service level routes
     *
     * @return void
     */
    public function loadRoutes(): void
    {
        $table = $this->fetchTable($this->_table);

        $defaultOptions = $this->routerDefaultOptions();
        $builder = ApiRouter::createRouteBuilder('/', []);
        $builder->scope('/', $defaultOptions, function (RouteBuilder $routes) use ($table, $defaultOptions) {
            $routes->setExtensions($this->_routeExtensions);
            $options = $defaultOptions;
            $options['map'] = array_merge($options['map'], [
                'describe' => ['action' => 'describe', 'method' => 'OPTIONS', 'path' => ''],
                'describeId' => ['action' => 'describe', 'method' => 'OPTIONS', 'path' => '{id}'],
            ]);
            $routes->resources($this->getName(), $options, function (RouteBuilder $routes) use ($table) {
                if (is_array($this->_routeExtensions)) {
                    $routes->setExtensions($this->_routeExtensions);

                    $keys = ['HasMany'/*, 'HasOne'*/];

                    foreach ($keys as $type) {
                        foreach ($table->associations()->getByType($type) as $assoc) {
                            /** @var \Cake\ORM\Association $assoc */
                            $target = $assoc->getTarget();
                            $alias = $target->getAlias();

                            $targetClass = get_class($target);
                            [, $className] = namespaceSplit($targetClass);
                            $className = preg_replace('/(.*)Table$/', '\1', $className);
                            if ($className === '') {
                                $className = $alias;
                            }
                            $this->_innerServices[] = Inflector::underscore($className);
                            $options = [
                                'map' => [
                                    'describe' => ['action' => 'describe', 'method' => 'OPTIONS', 'path' => ''],
                                    'describeId' => ['action' => 'describe', 'method' => 'OPTIONS', 'path' => '{id}'],
                                ],
                            ];
                            $routes->resources($className, $options);
                        }
                    }
                }
            });
        });
    }
}
