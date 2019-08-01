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

/**
 * Class DummyAction
 *
 * @package CakeDC\Api\Service\Action
 */
class DummyAction extends Action
{
    /**
     * Apply validation process.
     *
     * @return bool
     */
    public function validates(): bool
    {
        return true;
    }

    /**
     * Describe service.
     * For services that inherited from CrudService it provides action description using CrudDescribeAction.
     *
     * @return mixed
     */
    public function execute()
    {
        return null;
    }
}
