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

namespace CakeDC\Api\Service\Locator;

use CakeDC\Api\Service\ServiceRegistry;

/**
 * Contains method for setting and accessing LocatorInterface instance
 */
trait LocatorAwareTrait
{

    /**
     * Service locator instance
     *
     * @var \CakeDC\Api\Service\Locator\LocatorInterface
     */
    protected $_serviceLocator;

    /**
     * Sets the service locator.
     * If no parameters are passed, it will return the currently used locator.
     *
     * @param \CakeDC\Api\Service\Locator\LocatorInterface|null $serviceLocator LocatorInterface instance.
     * @return \CakeDC\Api\Service\Locator\LocatorInterface
     */
    public function serviceLocator(LocatorInterface $serviceLocator = null)
    {
        if ($serviceLocator !== null) {
            $this->_serviceLocator = $serviceLocator;
        }
        if (!$this->_serviceLocator) {
            $this->_serviceLocator = ServiceRegistry::locator();
        }

        return $this->_serviceLocator;
    }
}
