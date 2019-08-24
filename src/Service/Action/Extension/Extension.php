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

namespace CakeDC\Api\Service\Action\Extension;

use CakeDC\Api\Service\Action\ExtensionRegistry;
use Cake\Core\InstanceConfigTrait;

/**
 * Class Extension
 *
 * @package CakeDC\Api\Service\Action\Extension
 */
abstract class Extension
{
    use InstanceConfigTrait;

    protected $_defaultConfig = [];

    /**
     * ExtensionRegistry instance.
     *
     * @var \CakeDC\Api\Service\Action\ExtensionRegistry
     */
    protected $_registry;

    /**
     * Extension constructor.
     *
     * @param ExtensionRegistry $registry An ExtensionRegistry instance.
     * @param array $config Configuration.
     */
    public function __construct(ExtensionRegistry $registry, array $config = [])
    {
        $this->_registry = $registry;
        $this->setConfig($config);
    }

    /**
     * Method which used to define if extension need to be used directly from Action class.
     * By default is extensions is not attachable.
     *
     * @return bool
     */
    public function attachable()
    {
        return $this->getConfig('attachable') === true;
    }
}
