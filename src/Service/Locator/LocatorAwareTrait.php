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
     * Sets the table locator.
     *
     * @param \CakeDC\Api\Service\Locator\LocatorInterface $serviceLocator LocatorInterface instance.
     * @return self
     */
    public function setServiceLocator(LocatorInterface $serviceLocator)
    {
        $this->_serviceLocator = $serviceLocator;

        return $this;
    }

    /**
     * Gets the table locator.
     *
     * @return \CakeDC\Api\Service\Locator\LocatorInterface
     */
    public function getServiceLocator()
    {
        if (!$this->_serviceLocator) {
            $this->_serviceLocator = ServiceRegistry::getServiceLocator();
        }

        return $this->_serviceLocator;
    }
}
