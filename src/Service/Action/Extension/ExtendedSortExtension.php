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

namespace CakeDC\Api\Service\Action\Extension;

use CakeDC\Api\Service\Action\Action;
use Cake\Event\Event;
use Cake\Event\EventListenerInterface;
use Cake\ORM\Entity;

class ExtendedSortExtension extends Extension implements EventListenerInterface
{

    /**
     * Default settings
     *
     * @var array
     */
    protected $_defaultConfig = [
        'sortField' => 'sort',
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
        $action = $event->getSubject();
        $query = $event->getData('query');
        if ($event->result) {
            $query = $event->result;
        }
        $data = $action->data();
        $sort = null;

        $sortField = $this->getConfig('sortField');
        if (!empty($data[$sortField])) {
            $sort = json_decode($data[$sortField], true);
        }
        if (is_array($sort)) {
            $query->order($sort);
        }

        return $query;
    }
}
