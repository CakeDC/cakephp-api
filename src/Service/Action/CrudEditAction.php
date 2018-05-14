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

namespace CakeDC\Api\Service\Action;

use CakeDC\Api\Exception\ValidationException;

/**
 * Class CrudEditAction
 *
 * @package CakeDC\Api\Service\Action
 */
class CrudEditAction extends CrudAction
{

    /**
     * Execute action.
     *
     * @return mixed
     */
    public function execute()
    {
        $entity = $this->_getEntity($this->_id);
        $entity = $this->_patchEntity($entity, $this->data());

        return $this->_save($entity);
    }
}
