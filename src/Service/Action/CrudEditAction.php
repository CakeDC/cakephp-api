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

use CakeDC\Api\Exception\ValidationException;

/**
 * Class CrudEditAction
 *
 * @package CakeDC\Api\Service\Action
 */
class CrudEditAction extends CrudAction
{
    /**
     * Apply validation process.
     *
     * @return bool
     */
    public function validates(): bool
    {
        $validator = $this->getTable()->getValidator();
        $errors = $validator->errors($this->getData(), false);
        if (!empty($errors)) {
            throw new ValidationException(__('Validation failed'), 0, null, $errors);
        }

        return true;
    }

    /**
     * Execute action.
     *
     * @return mixed
     */
    public function execute()
    {
        $entity = $this->_getEntity($this->_id);
        $entity = $this->_patchEntity($entity, $this->getData());

        return $this->_save($entity);
    }
}
