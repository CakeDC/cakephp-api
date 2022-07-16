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

namespace CakeDC\Api\Service\Locator;

use Cake\Core\App;
use Cake\Core\Configure;
use Cake\Utility\Inflector;
use CakeDC\Api\Service\Service;
use RuntimeException;

/**
 * Provides a default registry/factory for Service objects.
 */
class ServiceLocator implements LocatorInterface
{
    /**
     * Configuration for aliases.
     */
    protected array $_config = [];

    /**
     * Instances that belong to the registry.
     */
    protected array $_instances = [];

    /**
     * Contains a list of Method objects that were created out of the
     * built-in Method class. The list is indexed by method names
     */
    protected array $_fallbacked = [];

    /**
     * Contains a list of options that were passed to get() method.
     */
    protected array $_options = [];

    /**
     * Stores a list of options to be used when instantiating an object
     * with a matching alias.
     *
     * @param string|array $alias Name of the alias or array to completely overwrite current config.
     * @param array|null $options list of options for the alias
     * @return \CakeDC\Api\Service\Locator\LocatorInterface
     * @throws \RuntimeException When you attempt to configure an existing table instance.
     */
    public function setConfig($alias, ?array $options = null): LocatorInterface
    {
        if (!is_string($alias)) {
            $this->_config = $alias;

            return $this;
        }

        if (isset($this->_instances[$alias])) {
            throw new RuntimeException(sprintf(
                'You cannot configure "%s", service has already been constructed.',
                $alias
            ));
        }

        $this->_config[$alias] = $options;

        return $this;
    }

    /**
     * Returns configuration for an alias or the full configuration array for all aliases.
     *
     * @param string|null $alias Alias to get config for, null for complete config.
     * @return array The config data.
     */
    public function getConfig(?string $alias = null): array
    {
        if ($alias === null) {
            return $this->_config;
        }

        return $this->_config[$alias] ?? [];
    }

    /**
     * Get a method instance from the registry.
     *
     * Methods are only created once until the registry is flushed.
     * This means that aliases must be unique across your application.
     * This is important because method associations are resolved at runtime
     * and cyclic references need to be handled correctly.
     *
     * The options that can be passed are the same as in `Method::__construct()`, but the
     * key `className` is also recognized.
     *
     * If $options does not contain `className` CakePHP will attempt to construct the
     * class name based on the alias. If this class does not exist,
     * then the default `CakeDC\OracleDriver\ORM\Method` class will be used. By setting the `className`
     * option you can define the specific class to use. This className can
     * use a plugin short class reference.
     *
     * If you use a `$name` that uses plugin syntax only the name part will be used as
     * key in the registry. This means that if two plugins, or a plugin and app provide
     * the same alias, the registry will only store the first instance.
     *
     * If no `method` option is passed, the method name will be the underscored version
     * of the provided $alias.
     *
     * If no `connection` option is passed the method's defaultConnectionName() method
     * will be called to get the default connection name to use.
     *
     * @param string $alias The alias name you want to get.
     * @param array $options The options you want to build the method with.
     *   If a method has already been loaded the options will be ignored.
     * @return \CakeDC\Api\Service\Service
     * @throws \RuntimeException When you try to configure an alias that already exists.
     */
    public function get(string $alias, array $options = []): Service
    {
        $alias = Inflector::camelize($alias);

        if (isset($this->_instances[$alias]) && empty($options['refresh'])) {
            if (!empty($options) && !$this->_compareOptions($alias, $options)) {
                throw new RuntimeException(sprintf(
                    'You cannot configure "%s", it already exists in the registry.',
                    $alias
                ));
            }

            return $this->_instances[$alias];
        }

        $this->_options[$alias] = $options;
        [, $classAlias] = pluginSplit($alias);
        $options = ['alias' => $classAlias] + $options;

        if (isset($this->_config[$alias])) {
            $options += $this->_config[$alias];
        }

        if (empty($options['className'])) {
            $options['className'] = Inflector::camelize($alias);
        }

        $className = $this->_getClassName($alias, $options);
        $lookupPlugins = Configure::read('Api.serviceLookupPlugins');
        $lookupMode = Configure::read('Api.lookupMode', 'underscore');
        if ($lookupPlugins === null) {
            $lookupPlugins = ['CakeDC/Api'];
        }
        if (!$className && strpos($options['className'], '.') === false) {
            foreach ($lookupPlugins as $candidate) {
                $_options = $options;
                if ($lookupMode === 'dasherize') {
                    $_options['className'] = Inflector::camelize(Inflector::underscore($_options['className']));
                }

                $_options['className'] = $candidate . '.' . $_options['className'];
                $className = $this->_getClassName($alias, $_options);
                if ($className) {
                    break;
                }
            }
        }
        if ($className) {
            if ($lookupMode === 'dasherize') {
                $options['className'] = Inflector::camelize(Inflector::underscore($className));
                $options['service'] = Inflector::dasherize($alias);
            } else {
                $options['className'] = $className;
                $options['service'] = Inflector::underscore($alias);
            }
        } else {
            $fallbackClass = Configure::read('Api.ServiceFallback');
            if ($fallbackClass) {
                $options['className'] = $fallbackClass;

                if ($lookupMode === 'dasherize') {
                    $options['service'] = Inflector::dasherize($alias);
                } else {
                    $options['service'] = Inflector::underscore($alias);
                }
            }
        }

        $this->_instances[$alias] = $this->_create($options);

        return $this->_instances[$alias];
    }

    /**
     * Gets the method class name.
     *
     * @param string $alias The alias name you want to get.
     * @param array $options Method options array.
     * @return string
     */
    protected function _getClassName(string $alias, array $options = []): ?string
    {
        $useVersions = Configure::read('Api.useVersioning');
        if ($useVersions) {
            $versionPrefix = Configure::read('Api.versionPrefix');
            if (empty($versionPrefix)) {
                $versionPrefix = 'v';
            }
            if (empty($options['version'])) {
                $options['version'] = $versionPrefix . Configure::read('Api.defaultVersion');
            }
            $version = '/' . $options['version'];
        } else {
            $version = '';
        }
        if (empty($options['className'])) {
            $options['className'] = Inflector::camelize($alias);
        }

        return App::className($options['className'], 'Service' . $version, 'Service');
    }

    /**
     * Wrapper for creating method instances
     *
     * @param array $options The alias to check for.
     * @return \CakeDC\Api\Service\Service
     */
    protected function _create(array $options): \CakeDC\Api\Service\Service
    {
        return new $options['className']($options);
    }

    /**
     * @inheritDoc
     */
    public function exists(string $alias): bool
    {
        return isset($this->_instances[$alias]);
    }

    /**
     * @inheritDoc
     */
    public function set(string $alias, Service $object): Service
    {
        return $this->_instances[$alias] = $object;
    }

    /**
     * @inheritDoc
     */
    public function clear(): void
    {
        $this->_instances = [];
        $this->_config = [];
        $this->_fallbacked = [];
    }

    /**
     * Returns the list of methods that were created by this registry that could
     * not be instantiated from a specific subclass. This method is useful for
     * debugging common mistakes when setting up associations or created new method
     * classes.
     *
     * @return array
     */
    public function genericInstances(): array
    {
        return $this->_fallbacked;
    }

    /**
     * @inheritDoc
     */
    public function remove(string $alias): void
    {
        unset(
            $this->_instances[$alias],
            $this->_config[$alias],
            $this->_fallbacked[$alias]
        );
    }

    /**
     * Compare services options.
     *
     * @param string $alias Service alias.
     * @param array $options Options.
     * @return bool
     */
    protected function _compareOptions(string $alias, array $options): bool
    {
        $currentOptions = $this->_options[$alias];
        unset($currentOptions['controller']);
        unset($options['controller']);

        return $currentOptions == $options;
    }
}
