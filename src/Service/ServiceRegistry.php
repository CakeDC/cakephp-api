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

use CakeDC\Api\Service\Locator\LocatorInterface;
use CakeDC\Api\Service\Locator\ServiceLocator;

class ServiceRegistry
{
    /**
     * LocatorInterface implementation instance.
     *
     * @var \CakeDC\Api\Service\Locator\LocatorInterface
     */
    protected static $_locator;

    /**
     * Default LocatorInterface implementation class.
     *
     * @var string
     */
    protected static $_defaultLocatorClass = ServiceLocator::class;

    /**
     * Returns a singleton instance of LocatorInterface implementation.
     *
     * @return \CakeDC\Api\Service\Locator\LocatorInterface
     */
    public static function getServiceLocator(): \CakeDC\Api\Service\Locator\LocatorInterface
    {
        if (static::$_locator === null) {
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
    public static function setServiceLocator(LocatorInterface $serviceLocator): void
    {
        static::$_locator = $serviceLocator;
    }
}
