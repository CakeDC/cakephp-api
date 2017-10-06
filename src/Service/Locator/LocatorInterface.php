<?php
/**
 * Copyright 2016 - 2017, Cake Development Corporation (http://cakedc.com)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright 2016 - 2017, Cake Development Corporation (http://cakedc.com)
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

namespace CakeDC\Api\Service\Locator;

use CakeDC\Api\Service\Service;

/**
 * Registries for Service objects should implement this interface.
 */
interface LocatorInterface
{

    /**
     * Stores a list of options to be used when instantiating an object
     * with a matching alias.
     *
     * @param string|null $alias Name of the alias
     * @param array|null $options list of options for the alias
     * @return array The config data.
     */
    public function config($alias = null, $options = null);

    /**
     * Get a service instance from the registry.
     *
     * @param string $alias The alias name you want to get.
     * @param array $options The options you want to build the service with.
     * @return \CakeDC\Api\Service\Service
     */
    public function get($alias, array $options = []);

    /**
     * Check to see if an instance exists in the registry.
     *
     * @param string $alias The alias to check for.
     * @return bool
     */
    public function exists($alias);

    /**
     * Set an instance.
     *
     * @param string $alias The alias to set.
     * @param \CakeDC\Api\Service\Service $object The service to set.
     * @return \CakeDC\Api\Service\Service
     */
    public function set($alias, Service $object);

    /**
     * Clears the registry of configuration and instances.
     *
     * @return void
     */
    public function clear();

    /**
     * Removes an instance from the registry.
     *
     * @param string $alias The alias to remove.
     * @return void
     */
    public function remove($alias);
}
