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

use Cake\ORM\Entity;
use Cake\Utility\Hash;
use CakeDC\Api\Exception\ValidationException;

/**
 * Class DeleteAction, uses POST and an array of entity ids to delete
 *
 * @package CakeDC\Api\Service\Action\Collection
 */
class DeleteAction extends CollectionAction
{
    public function validates()
    {
        $datas = $this->data();
        $this->_validateDataIsArray($datas);
        $index = 0;
        $pkKey = $this->getTable()->getPrimaryKey();
        $errors = collection($datas)->reduce(function ($errors, $data) use ($pkKey, &$index) {
            $error = null;
            if (empty(Hash::get($data, $pkKey))) {
                $error = [
                    $pkKey => ['_empty' => 'Missing id'],
                ];
            }
            if ($error) {
                $errors[$index] = $error;
            }

            $index++;

            return $errors;
        }, []);

        if (!empty($errors)) {
            throw new ValidationException(__('Validation failed, some keys missing for delete action'), 0, null, $errors);
        }

        return true;
    }

    /**
     * Execute action. Returns the array of deleted id's
     *
     * @return mixed
     */
    public function execute()
    {
        $entities = $this->_newEntities([
            'accessibleFields' => [$this->getTable()->getPrimaryKey() => true
            ]]);

        return $this->_deleteMany($entities);
    }

    /**
     * Delete many entities, atomic
     *
     * @param $entities
     * @return array
     */
    protected function _deleteMany($entities)
    {
        $deleted = [];
        $this->getTable()->getConnection()->transactional(function () use ($entities, &$deleted) {
            $errors = [];
            foreach ($entities as $index => $entity) {
                /**
                 * @var Entity $entity
                 */
                $entity->isNew(false);
                try {
                    $this->getTable()->deleteOrFail($entity, ['atomic' => false]);
                } catch (\InvalidArgumentException $ex) {
                    $errors[$index] = [
                        $entity->id => $ex->getMessage()
                    ];
                }
                $deleted[] = $entity->get($this->getTable()->getPrimaryKey());
            }

            if ($errors) {
                throw new ValidationException(__('Validation failed'), 0, null, $errors);
            }

            return true;
        });

        return $deleted;
    }
}
