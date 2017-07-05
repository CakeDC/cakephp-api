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

namespace CakeDC\Api\Service\Renderer\PayloadRenderer;

use CakeDC\Api\Collection;
use Cake\Core\App;
use RuntimeException;

class PayloadRendererCollection extends Collection
{

    /**
     * Creates PayloadRenderer instance.
     *
     * @param string $className PayloadRenderer class.
     * @param string $alias PayloadRenderer alias.
     * @param array $config Config array.
     * @return \CakeDC\Api\Service\Renderer\PayloadRenderer\PayloadRendererInterface
     * @throws \RuntimeException
     */
    protected function _create($className, $alias, $config)
    {
        $payloadRenderer = new $className($config);
        if (!($payloadRenderer instanceof PayloadRendererInterface)) {
            throw new RuntimeException(sprintf(
                'PayloadRenderer class `%s` must implement \CakeDC\Api\Service\Renderer\PayloadRenderer\PayloadRendererInterface',
                $className
            ));
        }

        return $payloadRenderer;
    }

    /**
     * Resolves PayloadRenderer class name.
     *
     * @param string $class Class name to be resolved.
     * @return string
     */
    protected function _resolveClassName($class)
    {
//        $result = App::className($class, 'Service/Action/Extension', 'Extension');
//        if ($result || strpos($class, '.') !== false) {
//            return $result;
//        }
//
//        return App::className('CakeDC/Api.' . $class, 'Service/Action/Extension', 'Extension');
//
        return App::className($class, 'Service/Renderer/PayloadRenderer', 'Renderer');
    }

    /**
     *
     * @param string $class Missing class.
     * @param string $plugin Class plugin.
     * @return void
     * @throws \RuntimeException
     */
    protected function _throwMissingClassError($class, $plugin)
    {
        $message = sprintf('PayloadRenderer class `%s` was not found.', $class);
        throw new RuntimeException($message);
    }
}
