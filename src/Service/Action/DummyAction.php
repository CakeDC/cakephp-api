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

use CakeDC\Api\Exception\ValidationException;
use CakeDC\Api\Service\CrudService;
use CakeDC\Api\Service\ServiceRegistry;
use Cake\Validation\Validator;

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
     * @return bool|array
     */
    public function validates()
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
