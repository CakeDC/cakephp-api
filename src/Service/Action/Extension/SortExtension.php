<?php
/**
 * Copyright 2016, Cake Development Corporation (http://cakedc.com)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright 2016, Cake Development Corporation (http://cakedc.com)
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

namespace CakeDC\Api\Service\Action\Extension;

use CakeDC\Api\Service\Action\Action;
use Cake\Event\Event;
use Cake\Event\EventListenerInterface;
use Cake\ORM\Entity;

/**
 * Class SortExtension
 *
 * @package CakeDC\Api\Service\Action\Extension
 */
class SortExtension extends Extension implements EventListenerInterface
{

    /**
     * @var array
     */
    protected $_defaultConfig = [
        'sortField' => 'sort',
        'directionField' => 'direction',
    ];

    /**
     * Returns a list of events this object is implementing. When the class is registered
     * in an event manager, each individual method will be associated with the respective event.
     *
     * @return array
     */
    public function implementedEvents()
    {
        return [
            'Action.Crud.onFindEntities' => 'findEntities',
        ];
    }

    /**
     * find entities
     *
     * @param Event $event An Event instance
     * @return Entity
     */
    public function findEntities(Event $event)
    {
        /** @var Action $action */
        $action = $event->subject();
        $query = $event->data['query'];
        if ($event->result) {
            $query = $event->result;
        }
        $data = $action->data();
        $direction = 'asc';
        $sort = null;

        $directionField = $this->config('directionField');
        $sortField = $this->config('sortField');
        if (!empty($data[$directionField])) {
            $direction = $data[$directionField];
        }
        if (!empty($data[$sortField])) {
            $sort = $data[$sortField];
            $query->order([$sort => $direction]);
        }

        return $query;
    }
}
