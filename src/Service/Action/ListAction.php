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

use Cake\Filesystem\Folder;
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
    public function initialize(array $config)
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
        $path = APP . 'Model' . DS . 'Table';
        $folder = new Folder($path);
        $tables = $folder->find('.*\.php');
        $services = collection($tables)
            ->map(function ($item) {
                preg_match('/^(.*)Table\.php/', $item, $replacedMatch);
                if (empty($replacedMatch[1])) {
                    return null;
                }

                return Inflector::underscore($replacedMatch[1]);
            })
            ->filter(function ($item) {
                return !empty($item);
            })
            ->toArray();

        return $services;
    }
}
