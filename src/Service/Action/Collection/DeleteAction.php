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

namespace CakeDC\Api\Service\Action\Collection;

use Cake\Utility\Hash;
use CakeDC\Api\Exception\ValidationException;
use InvalidArgumentException;
use Override;

/**
 * Class DeleteAction, uses POST and an array of entity ids to delete
 *
 * @package CakeDC\Api\Service\Action\Collection
 */
class DeleteAction extends CollectionAction
{
    /**
     * @inheritDoc
     */
    #[Override]
    public function validates(): bool
    {
        $data = $this->getData();
        $this->validateDataIsArray($data);
        $index = 0;
        $pkKey = $this->getTable()->getPrimaryKey();
        $errors = collection($data)->reduce(function ($errors, $data) use ($pkKey, &$index) {
            $pkKey = (array)$pkKey;
            $error = [];
            foreach ($pkKey as $key) {
                if (empty(Hash::get($data, $key))) {
                    $error[$key] = ['_empty' => 'Missing id'];
                }
            }
            if ($error !== []) {
                $errors[$index] = $error;
            }

            $index++;

            return $errors;
        }, []);

        if (!empty($errors)) {
            $message = __('Validation failed, some keys missing for delete action');
            throw new ValidationException($message, 0, null, $errors);
        }

        return true;
    }

    /**
     * Execute action. Returns the array of deleted id's
     *
     * @return array
     * @throws \Exception
     */
    public function execute(): array
    {
        $keys = $this->getTable()->getPrimaryKey();
        $accessibleFields = [];
        if (is_array($keys)) {
            foreach ($keys as $key) {
                $accessibleFields[$key] = true;
            }
        } else {
            $accessibleFields = [$keys => true];
        }
        $entities = $this->newEntities(['patchableFields' => $accessibleFields]);

        return $this->deleteMany($entities);
    }

    /**
     * Delete many entities, atomic
     *
     * @param array $entities entities
     * @return array
     * @throws \Exception
     */
    protected function deleteMany(array $entities): array
    {
        $deleted = [];
        $this->getTable()->getConnection()->transactional(function () use ($entities, &$deleted): true {
            $errors = [];
            $primaryKey = (array)$this->getTable()->getPrimaryKey();
            foreach ($entities as $index => $entity) {
                /** @var \Cake\ORM\Entity $entity */
                $entity->setNew(false);
                $missingKey = null;
                foreach ($primaryKey as $key) {
                    if (empty($entity->get($key))) {
                        $missingKey = $key;
                        break;
                    }
                }
                if ($missingKey !== null) {
                    $errors[$index] = [$missingKey => ['_empty' => 'Missing id']];
                    continue;
                }
                try {
                    $this->getTable()->deleteOrFail($entity, ['atomic' => false]);
                } catch (InvalidArgumentException $ex) {
                    $errors[$index] = [
                        $entity->id ?? '' => $ex->getMessage(),
                    ];
                }
                $deleted[] = $entity->get($this->getTable()->getPrimaryKey());
            }

            if ($errors !== []) {
                throw new ValidationException(__('Validation failed'), 0, null, $errors);
            }

            return true;
        });

        return $deleted;
    }
}
