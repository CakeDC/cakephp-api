<?php
declare(strict_types=1);

/**
 * Copyright 2016 - 2026, Cake Development Corporation (http://cakedc.com)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright 2016 - 2026, Cake Development Corporation (http://cakedc.com)
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

namespace CakeDC\Api\Test\TestCase\Service\Action\Extension;

use Cake\Core\InstanceConfigTrait;
use CakeDC\Api\Service\Action\Action;
use CakeDC\Api\Service\Action\Extension\SortableTrait;

/**
 * Exposes SortableTrait for unit tests.
 */
class SortableHarness
{
    use InstanceConfigTrait;
    use SortableTrait;

    /**
     * @var array
     */
    protected $_defaultConfig = [
        'sortableFields' => [],
    ];

    /**
     * @param array $config Extension config.
     */
    public function __construct(array $config = [])
    {
        $this->setConfig($config);
    }

    /**
     * @param \CakeDC\Api\Service\Action\Action|null $action Action instance.
     * @param mixed $field Requested sort field.
     * @return string|null
     */
    public function sortField(?Action $action, $field): ?string
    {
        return $this->_sortField($action, $field);
    }
}
