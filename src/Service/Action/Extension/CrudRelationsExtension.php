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

namespace CakeDC\Api\Service\Action\Extension;

use Cake\Event\Event;
use Cake\Event\EventListenerInterface;
use Cake\ORM\Association;
use Cake\ORM\Query;
use Cake\Utility\Inflector;
use CakeDC\Api\Service\Action\CrudAction;

/**
 * Class CrudRelationsExtension
 *
 * Allow to include relations for fetched entities to return by index, view or edit crud actions.
 * Currently limited only by HasOne and BelongsTo associations.
 *
 * @package CakeDC\Api\Service\Action\Extension
 */
class CrudRelationsExtension extends Extension implements EventListenerInterface
{
    /**
     * Returns a list of events this object is implementing. When the class is registered
     * in an event manager, each individual method will be associated with the respective event.
     *
     * @return array
     */
    public function implementedEvents(): array
    {
        return [
            'Action.Crud.onFindEntities' => 'findEntities',
            'Action.Crud.onFindEntity' => 'findEntity',
        ];
    }

    /**
     * On find entity
     *
     * @param \Cake\Event\Event $event An Event instance.
     * @return \Cake\ORM\Query
     */
    public function findEntity(Event $event): \Cake\ORM\Query
    {
        return $this->_attachAssociations($event->getSubject(), $event->getData('query'));
    }

    /**
     * On find entities.
     *
     * @param \Cake\Event\Event $event An Event instance.
     * @return \Cake\ORM\Query
     */
    public function findEntities(Event $event): \Cake\ORM\Query
    {
        return $this->_attachAssociations($event->getSubject(), $event->getData('query'));
    }

    /**
     * Checks if endpoint returns additional associations.
     *
     * @param \CakeDC\Api\Service\Action\CrudAction $action A CrudAction instance.
     * @return array|bool
     */
    protected function _includeAssociations(CrudAction $action)
    {
        $data = $action->getData();
        if (!empty($data['include_associations']) && empty($data['include_relations'])) {
            $data['include_relations'] = $data['include_associations'];
        }
        $exists = (is_array($data) && !empty($data['include_relations']));
        if (!$exists) {
            return false;
        }
        $associations = $data['include_relations'];
        if (!is_array($associations)) {
            return explode(',', $associations);
        }
        if (is_array($associations) && count($associations) > 0) {
            return $associations;
        }

        return false;
    }

    /**
     * Checks if endpoint returns direct associations, i.e. all belongsTo and all hasOne.
     *
     * @param \CakeDC\Api\Service\Action\CrudAction $action An CrudAction instance.
     * @return bool
     */
    protected function _includeDirectAssociations(CrudAction $action): bool
    {
        $data = $action->getData();

        return is_array($data) && !empty($data['include_direct']);
    }

    /**
     * @param \CakeDC\Api\Service\Action\CrudAction $action An Action instance.
     * @param \Cake\ORM\Query $query A Query instance.
     * @return mixed
     */
    protected function _attachAssociations(CrudAction $action, Query $query)
    {
        $associations = $this->_includeAssociations($action);
        if (empty($associations) && $this->_includeDirectAssociations($action)) {
            $relations = $action
                ->getTable()
                ->associations()
                ->getByType(['HasOne', 'BelongsTo']);
            $associations = collection($relations)
                ->map(function (Association $relation) {
                    return $relation->getTarget()->getTable();
                })
                ->toArray();
        }
        if (empty($associations)) {
            return $query;
        }

        $tables = collection($associations)
            ->map(function ($name) {
                return Inflector::camelize($name);
            })
            ->toArray();

        collection($tables)->each(function ($name) use ($query, $action) {
            $assoc = $action->getTable()->getAssociation($name);
            if ($assoc !== null) {
                $query->select($assoc);
            }
        });
        $query->select($action->getTable());
        $query->contain($tables);

        return $query;
    }
}
