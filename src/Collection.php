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

namespace CakeDC\Api;

use ArrayIterator;
use Cake\Core\InstanceConfigTrait;
use Cake\Core\ObjectRegistry;
use IteratorAggregate;

abstract class Collection extends ObjectRegistry implements IteratorAggregate
{
    use InstanceConfigTrait;

    /**
     * Config array.
     */
    protected array $_defaultConfig = [];

    /**
     * Constructor
     *
     * @param array $config Configuration
     * @throws \Exception
     */
    public function __construct(array $config = [])
    {
        $this->setConfig($config);

        foreach ($config as $key => $value) {
            if (is_int($key)) {
                $this->load($value);
                continue;
            }
            $this->load($key, $value);
        }
    }

    /**
     * Returns true if a collection is empty.
     *
     * @return bool
     */
    public function isEmpty(): bool
    {
        return empty($this->_loaded);
    }

    /**
     * Returns iterator.
     *
     * @return \ArrayIterator
     */
    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->_loaded);
    }
}
