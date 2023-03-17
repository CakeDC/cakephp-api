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

namespace CakeDC\Api\Service\Action;

// use Shim\Filesystem\Folder;
use Cake\Utility\Inflector;

/**
 * Class ListAction
 *
 * @package CakeDC\Api\Service\Action
 */
class ListAction extends Action
{
    /**
     * Initialize an action instance
     *
     * @param array $config Configuration options passed to the constructor
     * @return void
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->Auth->allow($this->getName());
    }

    /**
     * Execute action.
     *
     * @return mixed
     */
    public function execute()
    {
        $path = APP . 'Model' . DS . 'Table' . DS;
        $tables = [];
        foreach (glob($path . '*.php') as $file) {
            $name = str_replace($path, '', $file);
            if (!empty($name)) {
                $tables[] = $name;
            }
        }

        return collection($tables)
            ->map(function ($item) {
                preg_match('/^(.*)Table\.php/', $item, $replacedMatch);
                if (empty($replacedMatch[1])) {
                    return null;
                }

                return Inflector::underscore($replacedMatch[1]);
            })
            ->filter(fn($item) => !empty($item))
            ->toArray();
    }
}
