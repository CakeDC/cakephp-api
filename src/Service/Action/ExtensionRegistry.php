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

namespace CakeDC\Api\Service\Action;

use CakeDC\Api\Service\Exception\MissingExtensionException;
use Cake\Core\App;
use Cake\Core\ObjectRegistry;
use Cake\Event\EventDispatcherTrait;

/**
 * Class ExtensionRegistry
 *
 * @package CakeDC\Api\Service\Action
 */
class ExtensionRegistry extends ObjectRegistry
{

    use EventDispatcherTrait;

    /**
     * The Action that this collection was initialized with.
     *
     * @var Action
     */
    protected $_action = null;

    /**
     * Constructor.
     *
     * @param Action $action Action instance.
     */
    public function __construct(Action $action = null)
    {
        if ($action) {
            $this->_action = $action;
        }
    }
    /**
     * Should resolve the classname for a given object type.
     *
     * @param string $class The class to resolve.
     * @return string|false The resolved name or false for failure.
     */
    protected function _resolveClassName($class)
    {
        $result = App::className($class, 'Service/Action/Extension', 'Extension');
        if ($result || strpos($class, '.') !== false) {
            return $result;
        }

        return App::className('CakeDC/Api.' . $class, 'Service/Action/Extension', 'Extension');
    }

    /**
     * Throw an exception when the requested object name is missing.
     *
     * @param string $class The class that is missing.
     * @param string $plugin The plugin $class is missing from.
     * @return void
     * @throws \Exception
     */
    protected function _throwMissingClassError($class, $plugin)
    {
        throw new MissingExtensionException([
            'class' => $class . 'Extension',
            'plugin' => $plugin
        ]);
    }

    /**
     * Create an instance of a given classname.
     *
     * This method should construct and do any other initialization logic
     * required.
     *
     * @param string $class The class to build.
     * @param string $alias The alias of the object.
     * @param array $config The Configuration settings for construction
     * @return mixed
     */
    protected function _create($class, $alias, $config)
    {
        if (empty($config['action'])) {
            $config['action'] = $this->_action;
        }
        $instance = new $class($this, $config);
        $this->getEventManager()->on($instance);

        return $instance;
    }
}
