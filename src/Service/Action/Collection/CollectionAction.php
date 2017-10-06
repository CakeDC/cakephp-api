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

namespace CakeDC\Api\Service\Action\Collection;

use CakeDC\Api\Exception\ValidationException;
use CakeDC\Api\Service\Action\CrudAction;
use Cake\Utility\Hash;

/**
 * Class CollectionAction
 *
 * @package CakeDC\Api\Service\Action\Collection
 */
abstract class CollectionAction extends CrudAction
{

    /**
     * Apply validation process to many entities
     *
     * @return bool
     */
    protected function _validateMany()
    {
        $validator = $this->getTable()->validator();
        $datas = $this->data();
        $this->_validateDataIsArray($datas);
        $index = 0;
        $errors = collection($datas)->reduce(function ($errors, $data) use ($validator, &$index) {
            $error = $validator->errors($data);
            if ($error) {
                $errors[$index] = $error;
            }

            $index++;

            return $errors;
        }, []);

        if (!empty($errors)) {
            throw new ValidationException(__('Validation failed'), 0, null, $errors);
        }

        return true;
    }

    /**
     * Save many entities
     *
     * @param array $entities entities
     * @return array of entities saved
     */
    protected function _saveMany($entities = [])
    {
        if ($this->getTable()->saveMany($entities)) {
            return $entities;
        } else {
            $errors = collection($entities)->reduce(function ($errors, $entity) {
                $errors[] = $entity->errors();
            }, []);
            throw new ValidationException(__('Validation on {0} failed', $this->getTable()->getAlias()), 0, null, $errors);
        }
    }

    /**
     * Create entities from the posted data
     *
     * @param array $patchOptions options to use un patch
     * @return array entities
     */
    protected function _newEntities($patchOptions = [])
    {
        $datas = $this->data();
        $this->_validateDataIsArray($datas);

        return collection($datas)->reduce(function ($entities, $data) use ($patchOptions) {
            $entity = $this->_newEntity();
            $entity = $this->_patchEntity($entity, $data, $patchOptions);
            $entities[] = $entity;

            return $entities;
        }, []);
    }

    /**
     * Ensure the data is a not empty array
     *
     * @param mixed $datas posted data
     * @throws ValidationException
     * @return void
     */
    protected function _validateDataIsArray($datas)
    {
        if (!is_array($datas) || Hash::dimensions($datas) < 2) {
            throw new ValidationException(__('Validation failed, POST data is not an array of items'), 0, null);
        }
    }
}
