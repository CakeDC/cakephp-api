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

use Cake\Utility\Hash;
use Cake\Utility\Inflector;

/**
 * Class NestedCrudService
 *
 * @package CakeDC\Api\Service
 */
abstract class NestedCrudService extends CrudService
{
    protected ?string $parentIdName = null;

    /**
     * NestedCrudService constructor.
     *
     * @param array $config Service settings.
     */
    public function __construct(array $config = [])
    {
        parent::__construct($config);
        if (isset($config['parentIdName'])) {
            $this->parentIdName = $config['parentIdName'];
        }
    }

    /**
     * Action constructor options.
     *
     * @param array $route Action route,
     * @return array
     */
    #[\Override]
    protected function actionOptions(array $route): array
    {
        $parent = $this->getParentService();
        if ($this->parentIdName === null && $parent instanceof Service) {
            $parentName = $parent->getName();
            $parentIdName = Inflector::singularize($parentName) . '_id';
            if (array_key_exists($parentIdName, $route)) {
                $this->parentIdName = $parentIdName;
            }
        }
        $parentId = null;
        if ($this->parentIdName !== null && isset($route[$this->parentIdName])) {
            $parentId = $route[$this->parentIdName];
        }
        $options = [
            'parentId' => $parentId,
            'parentIdName' => $this->parentIdName,
        ];
        if ($parentId !== null) {
            $options['Extension'] = ['CakeDC/Api.Nested'];
        }

        return Hash::merge(parent::actionOptions($route), $options);
    }
}
