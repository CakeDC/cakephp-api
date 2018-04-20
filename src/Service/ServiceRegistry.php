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

use CakeDC\Api\Service\Locator\LocatorInterface;
use Cake\Event\EventDispatcherInterface;
use Cake\Event\EventDispatcherTrait;

class ServiceRegistry implements EventDispatcherInterface
{

    use EventDispatcherTrait;

    /**
     * LocatorInterface implementation instance.
     *
     * @var \Cake\ORM\Locator\LocatorInterface
     */
    protected static $_locator;

    /**
     * Default LocatorInterface implementation class.
     *
     * @var string
     */
    protected static $_defaultLocatorClass = 'CakeDC\Api\Service\Locator\ServiceLocator';

    /**
     * Sets and returns a singleton instance of LocatorInterface implementation.
     *
     * @param \CakeDC\Api\Service\Locator\LocatorInterface|null $locator Instance of a locator to use.
     * @return \CakeDC\Api\Service\Locator\LocatorInterface
     * @deprecated 3.5.0 Use getServiceLocator()/setServiceLocator() instead.
     */
    public static function locator(LocatorInterface $locator = null)
    {
        deprecationWarning(
            'TableRegistry::locator() is deprecated. ' .
            'Use setServiceLocator()/getServiceLocator() instead.'
        );
        if ($locator) {
            static::setServiceLocator($locator);
        }

        return static::getServiceLocator();
    }

    /**
     * Returns a singleton instance of LocatorInterface implementation.
     *
     * @return \CakeDC\Api\Service\Locator\LocatorInterface
     */
    public static function getServiceLocator()
    {
        if (!static::$_locator) {
            static::$_locator = new static::$_defaultLocatorClass();
        }

        return static::$_locator;
    }

    /**
     * Sets singleton instance of LocatorInterface implementation.
     *
     * @param \CakeDC\Api\Service\Locator\LocatorInterface $serviceLocator Instance of a locator to use.
     * @return void
     */
    public static function setServiceLocator(LocatorInterface $serviceLocator)
    {
        static::$_locator = $serviceLocator;
    }

    /**
     * Stores a list of options to be used when instantiating an object
     * with a matching alias.
     *
     * @param string|null $alias Name of the alias
     * @param array|null $options list of options for the alias
     * @return array The config data.
     * @deprecated 3.6.0 Use \CakeDC\Api\Service\Locator\ServiceLocator::getConfig()/setConfig() instead.
     */
    public static function config($alias = null, $options = null)
    {
        deprecationWarning(
            'ServiceRegistry::config() is deprecated. ' .
            'Use \CakeDC\Api\Service\Locator\ServiceLocator::getConfig()/setConfig() instead.'
        );

        return static::getServiceLocator()->config($alias, $options);
    }

    /**
     * Get a table instance from the registry.
     *
     * @param string $alias The alias name you want to get.
     * @param array $options The options you want to build the table with.
     * @return \CakeDC\Api\Service\Service
     * @deprecated 3.6.0 Use \CakeDC\Api\Service\Locator\ServiceLocator::get() instead.
     */
    public static function get($alias, array $options = [])
    {
        return static::getServiceLocator()->get($alias, $options);
    }

    /**
     * Check to see if an instance exists in the registry.
     *
     * @param string $alias The alias to check for.
     * @return bool
     * @deprecated 3.6.0 Use \CakeDC\Api\Service\Locator\ServiceLocator::exists() instead.
     */
    public static function exists($alias)
    {
        return static::getServiceLocator()->exists($alias);
    }

    /**
     * Set an instance.
     *
     * @param string $alias The alias to set.
     * @param \CakeDC\Api\Service\Service $object The table to set.
     * @return \CakeDC\Api\Service\Service
     * @deprecated 3.6.0 Use \CakeDC\Api\Service\Locator\ServiceLocator::set() instead.
     */
    public static function set($alias, Service $object)
    {
        return static::getServiceLocator()->set($alias, $object);
    }

    /**
     * Removes an instance from the registry.
     *
     * @param string $alias The alias to remove.
     * @return void
     * @deprecated 3.6.0 Use \CakeDC\Api\Service\Locator\ServiceLocator::remove() instead.
     */
    public static function remove($alias)
    {
        static::getServiceLocator()->remove($alias);
    }

    /**
     * Clears the registry of configuration and instances.
     *
     * @return void
     * @deprecated 3.6.0 Use \CakeDC\Api\Service\Locator\ServiceLocator::clear() instead.
     */
    public static function clear()
    {
        static::getServiceLocator()->clear();
    }

    /**
     * Proxy for static calls on a locator.
     *
     * @param string $name Method name.
     * @param array $arguments Method arguments.
     * @return mixed
     */
    public static function __callStatic($name, $arguments)
    {
        deprecationWarning(
            'TableRegistry::' . $name . '() is deprecated. ' .
            'Use \CakeDC\Api\Service\Locator\ServiceLocator::' . $name . '() instead.'
        );

        return call_user_func_array([static::getServiceLocator(), $name], $arguments);
    }
}
