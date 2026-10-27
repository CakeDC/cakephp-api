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
use JsonException;

class ExtendedSortExtension extends Extension implements EventListenerInterface
{
    use SortableTrait;

    /**
     * Default settings
     *
     * - `sortableFields`: allowed sort fields. When empty, only action table columns the entity does not hide are allowed.
     *
     * @var array
     */
    protected $_defaultConfig = [
        'sortField' => 'sort',
        'sortableFields' => [],
    ];

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
        ];
    }

    /**
     * find entities
     *
     * @param \Cake\Event\Event $event An Event instance
     * @return \Cake\ORM\Query
     */
    public function findEntities(Event $event): \Cake\ORM\Query
    {
        $action = $event->getSubject();
        $query = $event->getData('query');
        if ($event->getResult()) {
            $query = $event->getResult();
        }
        $data = $action->getData();

        $sort = [];
        $sortField = $this->getConfig('sortField');
        if (!empty($data[$sortField]) && is_string($data[$sortField])) {
            try {
                $sort = json_decode($data[$sortField], true, 512, JSON_THROW_ON_ERROR);
            } catch (JsonException $e) {
                $sort = [];
            }
        }

        $order = [];
        foreach (is_array($sort) ? $sort : [] as $field => $direction) {
            $field = $this->_sortField($action, $field);
            if ($field !== null && is_string($direction)) {
                $order[$field] = $this->_sortDirection($direction);
            }
        }
        if ($order !== []) {
            $query->order($order);
        }

        return $query;
    }
}
