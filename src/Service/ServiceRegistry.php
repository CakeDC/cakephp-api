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
     */
    public static function locator(LocatorInterface $locator = null)
    {
        if ($locator) {
            static::$_locator = $locator;
        }

        if (!static::$_locator) {
            static::$_locator = new static::$_defaultLocatorClass;
        }

        return static::$_locator;
    }

    /**
     * Stores a list of options to be used when instantiating an object
     * with a matching alias.
     *
     * @param string|null $alias Name of the alias
     * @param array|null $options list of options for the alias
     * @return array The config data.
     */
    public static function config($alias = null, $options = null)
    {
        return static::locator()->config($alias, $options);
    }

    /**
     * Get a table instance from the registry.
     *
     * @param string $alias The alias name you want to get.
     * @param array $options The options you want to build the table with.
     * @return \CakeDC\Api\Service\Service
     */
    public static function get($alias, array $options = [])
    {
        return static::locator()->get($alias, $options);
    }

    /**
     * Check to see if an instance exists in the registry.
     *
     * @param string $alias The alias to check for.
     * @return bool
     */
    public static function exists($alias)
    {
        return static::locator()->exists($alias);
    }

    /**
     * Set an instance.
     *
     * @param string $alias The alias to set.
     * @param \CakeDC\Api\Service\Service $object The table to set.
     * @return \CakeDC\Api\Service\Service
     */
    public static function set($alias, Service $object)
    {
        return static::locator()->set($alias, $object);
    }

    /**
     * Removes an instance from the registry.
     *
     * @param string $alias The alias to remove.
     * @return void
     */
    public static function remove($alias)
    {
        static::locator()->remove($alias);
    }

    /**
     * Clears the registry of configuration and instances.
     *
     * @return void
     */
    public static function clear()
    {
        static::locator()->clear();
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
        return call_user_func_array([static::locator(), $name], $arguments);
    }
}
