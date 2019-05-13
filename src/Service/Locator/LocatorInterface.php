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

use CakeDC\Api\Service\Service;

/**
 * Registries for Service objects should implement this interface.
 */
interface LocatorInterface
{
    /**
     * Returns configuration for an alias or the full configuration array for
     * all aliases.
     *
     * @param string|null $alias Alias to get config for, null for complete config.
     * @return array The config data.
     */
    public function getConfig(?string $alias = null): array;

    /**
     * Stores a list of options to be used when instantiating an object
     * with a matching alias.
     *
     * @param string|array $alias Name of the alias or array to completely
     *   overwrite current config.
     * @param array|null $options list of options for the alias
     * @return $this
     * @throws \RuntimeException When you attempt to configure an existing
     *   table instance.
     */
    public function setConfig($alias, $options = null);

    /**
     * Get a service instance from the registry.
     *
     * @param string $alias The alias name you want to get.
     * @param array $options The options you want to build the service with.
     * @return \CakeDC\Api\Service\Service
     */
    public function get(string $alias, array $options = []): Service;

    /**
     * Check to see if an instance exists in the registry.
     *
     * @param string $alias The alias to check for.
     * @return bool
     */
    public function exists(string $alias): bool;

    /**
     * Set an instance.
     *
     * @param string $alias The alias to set.
     * @param \CakeDC\Api\Service\Service $object The service to set.
     * @return \CakeDC\Api\Service\Service
     */
    public function set(string $alias, Service $object): Service;

    /**
     * Clears the registry of configuration and instances.
     *
     * @return void
     */
    public function clear(): void;

    /**
     * Removes an instance from the registry.
     *
     * @param string $alias The alias to remove.
     * @return void
     */
    public function remove(string $alias): void;
}
