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

use Cake\Event\EventInterface;
use Cake\Event\EventListenerInterface;
use Cake\ORM\Query;
use CakeDC\Api\Service\Action\Action;

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
    public function implementedEvents(): array
    {
        return [
            'Action.Crud.onFindEntities' => 'findEntities',
            'Action.Crud.afterFindEntities' => 'afterFind',
        ];
    }

    /**
     * Find entities
     *
     * @param \Cake\Event\EventInterface $event An Event instance
     * @return \Cake\ORM\Query
     */
    public function findEntities(EventInterface $event): Query
    {
        /** @var \CakeDC\Api\Service\Action\Action $action */
        $action = $event->getSubject();
        $query = $event->getData('query');
        if ($event->getResult()) {
            $query = $event->getResult();
        }
        $query->limit($this->_limit($action));
        $query->page($this->_page($action));

        return $query;
    }

    /**
     * Returns current page.
     *
     * @param \CakeDC\Api\Service\Action\Action $action An Action instance
     * @return int
     */
    protected function _page(Action $action): int
    {
        $data = $action->getData();
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
     * @param \CakeDC\Api\Service\Action\Action $action An Action instance
     * @return mixed
     */
    protected function _limit(Action $action)
    {
        $data = $action->getData();
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
     * @param \Cake\Event\EventInterface $event An Event instance
     * @return void
     */
    public function afterFind(EventInterface $event): void
    {
        /** @var \CakeDC\Api\Service\Action\Action $action */
        $action = $event->getSubject();
        $query = $event->getData('query');
        $result = $action->getService()->getResult();
        $count = $query->count();
        $limit = $this->_limit($action);
        $pagination = [
            'page' => $this->_page($action),
            'limit' => $limit,
            'pages' => ceil($count / $limit),
            'count' => $count,
        ];
        $result->appendPayload('pagination', $pagination);
    }
}
