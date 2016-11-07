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

namespace CakeDC\Api\Service;

use CakeDC\Api\Routing\ApiRouter;
use Cake\ORM\TableRegistry;
use Cake\Routing\RouteBuilder;
use Cake\Utility\Inflector;

/**
 * Class FallbackService
 *
 * @package CakeDC\Api\Service
 */
class FallbackService extends NestedCrudService
{

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
    public function initialize()
    {
        parent::initialize();
        if (empty($this->_table)) {
            $this->_table = Inflector::pluralize(Inflector::camelize($this->name()));
        }
    }

    /**
     * Initialize service level routes
     *
     * @return void
     */
    public function loadRoutes()
    {
        $table = TableRegistry::get($this->_table);

        $defaultOptions = $this->routerDefaultOptions();
        ApiRouter::scope('/', $defaultOptions, function (RouteBuilder $routes) use ($table, $defaultOptions) {
            $routes->extensions($this->_extensions);
            $options = $defaultOptions;
            $options['map'] = array_merge($options['map'], [
                'describe' => ['action' => 'describe', 'method' => 'OPTIONS', 'path' => ''],
                'describeId' => ['action' => 'describe', 'method' => 'OPTIONS', 'path' => ':id'],
            ]);
            $routes->resources($this->name(), $options, function ($routes) use ($table) {
                if (is_array($this->_extensions)) {
                    $routes->extensions($this->_extensions);

                    $keys = ['HasMany'/*, 'HasOne'*/];

                    foreach ($keys as $type) {
                        foreach ($table->associations()
                                       ->type($type) as $assoc) {
                            $target = $assoc->target();
                            $alias = $target->alias();

                            $targetClass = get_class($target);
                            list(, $className) = namespaceSplit($targetClass);
                            $className = preg_replace('/(.*)Table$/', '\1', $className);
                            if ($className === '') {
                                $className = $alias;
                            }
                            $this->_innerServices[] = Inflector::underscore($className);
                            $options = [
                                'map' => [
                                    'describe' => ['action' => 'describe', 'method' => 'OPTIONS', 'path' => ''],
                                    'describeId' => ['action' => 'describe', 'method' => 'OPTIONS', 'path' => ':id'],
                                ]
                            ];
                            $routes->resources($className, $options);
                        }
                    }
                }
            });
        });
    }
}
