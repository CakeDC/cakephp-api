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

/**
 * Class SortExtension
 *
 * @package CakeDC\Api\Service\Action\Extension
 */
class SortExtension extends Extension implements EventListenerInterface
{
    use SortableTrait;

    /**
     * Default settings
     *
     * - `sortableFields`: allowed sort fields. When empty, only action table columns the entity does not hide are allowed.
     *
     * @var array
     */
    protected array $_defaultConfig = [
        'sortField' => 'sort',
        'directionField' => 'direction',
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
     * @return void
     */
    public function findEntities(Event $event): void
    {
        $action = $event->getSubject();
        $query = $event->getData('query');
        if ($event->getResult()) {
            $query = $event->getResult();
        }
        $data = $action->getData();

        $sort = $this->_sortField($action, $data[$this->getConfig('sortField')] ?? null);
        if ($sort !== null) {
            $direction = $this->_sortDirection($data[$this->getConfig('directionField')] ?? null);
            $query->orderBy([$sort => $direction]);
        }

        $event->setResult($query);
    }
}
