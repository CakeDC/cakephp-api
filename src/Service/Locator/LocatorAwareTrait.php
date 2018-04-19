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
     * @deprecated 3.6.0 Use getTableLocator()/setTableLocator() instead.
     */
    public function serviceLocator(LocatorInterface $serviceLocator = null)
    {
        deprecationWarning(
            get_called_class() . '::tableLocator() is deprecated. ' .
            'Use getTableLocator()/setTableLocator() instead.'
        );
        if ($tableLocator !== null) {
            $this->setTableLocator($tableLocator);
        }

        return $this->getTableLocator();
    }

    /**
     * Sets the table locator.
     *
     * @param \Cake\ORM\Locator\LocatorInterface $tableLocator LocatorInterface instance.
     * @return $this
     */
    public function setTableLocator(LocatorInterface $tableLocator)
    {
        $this->_tableLocator = $tableLocator;

        return $this;
    }

    /**
     * Gets the table locator.
     *
     * @return \Cake\ORM\Locator\LocatorInterface
     */
    public function getTableLocator()
    {
        if (!$this->_tableLocator) {
            $this->_tableLocator = TableRegistry::getTableLocator();
        }

        return $this->_tableLocator;
    }
}
