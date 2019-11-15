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

namespace CakeDC\Api\Service\Extension;

use Cake\Core\InstanceConfigTrait;
use CakeDC\Api\Service\ExtensionRegistry;

/**
 * Class Extension
 *
 * @package CakeDC\Api\Service\Extension
 */
abstract class Extension
{
    use InstanceConfigTrait;

    protected $_defaultConfig = [];

    /**
     * ExtensionRegistry instance.
     *
     * @var \CakeDC\Api\Service\ExtensionRegistry
     */
    protected $_registry;

    /**
     * Extension constructor.
     *
     * @param \CakeDC\Api\Service\ExtensionRegistry $registry An ExtensionRegistry instance.
     * @param array $config Configuration.
     */
    public function __construct(ExtensionRegistry $registry, array $config = [])
    {
        $this->_registry = $registry;
        $this->setConfig($config);
    }
}
