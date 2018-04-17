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

/**
 * Class PaginateExtension
 *
 * @package CakeDC\Api\Service\Action\Extension
 */
class PaginateExtension extends Extension implements EventListenerInterface
{

    protected $_defaultConfig = [
        'defaultLimit' => 20,
        'limitField' => 'limit',
        'pageField' => 'page',
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
            'Action.Crud.afterFindEntities' => 'afterFind',
        ];
    }

    /**
     * Find entities
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
        $query->limit($this->_limit($action));
        $query->page($this->_page($action));

        return $query;
    }

    /**
     * Returns current page.
     *
     * @param Action $action An Action instance
     * @return int
     */
    protected function _page(Action $action)
    {
        $data = $action->data();
        $pageField = $this->getConfig('pageField');
        if (!empty($data[$pageField]) && is_numeric($data[$pageField])) {
            return (int)$data[$pageField];
        } else {
            return 1;
        }
    }

    /**
     * Returns current limit
     *
     * @param Action $action An Action instance
     * @return mixed
     */
    protected function _limit(Action $action)
    {
        $data = $action->data();
        $limitField = $this->getConfig('limitField');
        $maxLimit = $action->getConfig($limitField);
        if (empty($maxLimit)) {
            $maxLimit = $this->getConfig('defaultLimit');
        }
        if (!empty($limitField) && !empty($data[$limitField]) && is_numeric($data[$limitField])) {
            $limit = min((int)$data[$limitField], $maxLimit);

            return $limit;
        } else {
            return $maxLimit;
        }
    }

    /**
     * after find entities
     *
     * @param Event $event An Event instance
     * @return void
     */
    public function afterFind(Event $event)
    {
        $action = $event->getSubject();
        $query = $event->getData('query');
        $result = $action->getService()->getResult();
        $count = $query->count();
        $limit = $this->_limit($action);
        $pagination = [
            'page' => $this->_page($action),
            'limit' => $limit,
            'pages' => ceil($count / $limit),
            'count' => $count
        ];
        $result->setPayload('pagination', $pagination);
    }
}
