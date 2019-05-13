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
use Cake\ORM\Query;
use CakeDC\Api\Service\Action\CrudAction;

/**
 * Class CrudAutocompleteListExtension
 *
 * @package CakeDC\Api\Service\Action\Extension
 */
class CrudAutocompleteListExtension extends Extension implements EventListenerInterface
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
        ];
    }

    /**
     * On find entities.
     *
     * @param \Cake\Event\Event $Event An Event instance.
     * @return \Cake\ORM\Query
     */
    public function findEntities(Event $Event)
    {
        return $this->_autocompleteList($Event->getSubject(), $Event->getData('query'));
    }

    /**
     * @param \CakeDC\Api\Service\Action\CrudAction $action An Action instance.
     * @param \Cake\ORM\Query $query A Query instance.
     * @return \Cake\ORM\Query
     */
    protected function _autocompleteList(CrudAction $action, Query $query)
    {
        $data = $action->getData();
        if (!(is_array($data) && !empty($data['autocomplete_list']))) {
            return $query;
        }
        $query = $query->select([
            $action->getTable()->getPrimaryKey(),
            $action->getTable()->getDisplayField(),
        ]);

        return $query;
    }
}
