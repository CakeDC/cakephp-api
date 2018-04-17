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
use CakeDC\Api\Service\Action\ExtensionRegistry;
use CakeDC\Api\Service\Utility\ReverseRouting;
use Cake\Event\Event;
use Cake\Event\EventListenerInterface;
use Cake\ORM\Entity;

/**
 * Class CursorPaginateExtension
 *
 * @package CakeDC\Api\Service\Action\Extension
 */
class CursorPaginateExtension extends Extension implements EventListenerInterface
{

    protected $_defaultConfig = [
        'cursorField' => 'id',
        'countField' => 'count',
        'defaultCount' => 20,
        'maxIdField' => 'max_id',
        'sinceIdField' => 'since_id',
    ];

    /**
     * @var ReverseRouting
     */
    protected $_reverseRouter;

    /**
     * CursorPaginateExtension constructor.
     *
     * @param ExtensionRegistry $registry An Extension Registry instance.
     * @param array $config Configuration.
     * @internal param array $options
     */
    public function __construct(ExtensionRegistry $registry, array $config = [])
    {
        parent::__construct($registry, $config);
        $this->_reverseRouter = new ReverseRouting();
    }

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
     * find entities
     *
     * @param Event $event An Event instance.
     * @return Entity
     */
    public function findEntities(Event $event)
    {
        $action = $event->getSubject();
        $query = $event->getData('query');
        if ($event->result) {
            $query = $event->result;
        }
        $query->limit($this->_count($action));
        $sinceId = $this->_sinceId($action);
        $maxId = $this->_maxId($action);
        $orderDirection = 'desc';
        $cursorField = $this->getConfig('cursorField');
        if ($maxId) {
            $orderDirection = 'desc';
            $query->where([$cursorField . ' <' => $maxId]);
        } elseif ($sinceId) {
            $orderDirection = 'asc';
            $query->where([$cursorField . ' >' => $sinceId]);
        }
        $query->order([$cursorField => $orderDirection]);

        return $query;
    }

    /**
     * Returns since id.
     *
     * @param Action $action An Action instance.
     * @return int|null
     */
    protected function _sinceId(Action $action)
    {
        $data = $action->data();
        $sinceIdField = $this->getConfig('sinceIdField');
        if (!empty($sinceIdField) && !empty($data[$sinceIdField]) && is_numeric($data[$sinceIdField])) {
            return (int)$data[$sinceIdField];
        } else {
            return null;
        }
    }

    /**
     * Returns max id.
     *
     * @param Action $action An Action instance.
     * @return int|null
     */
    protected function _maxId(Action $action)
    {
        $data = $action->data();
        $maxIdField = $this->getConfig('maxIdField');
        if (!empty($maxIdField) && !empty($data[$maxIdField]) && is_numeric($data[$maxIdField])) {
            return (int)$data[$maxIdField];
        } else {
            return null;
        }
    }

    /**
     * Returns count.
     *
     * @param Action $action An Action instance.
     * @return int|null
     */
    protected function _count(Action $action)
    {
        $data = $action->data();
        $countField = $this->getConfig('countField');
        $maxCount = $this->getConfig('defaultCount');
        if (!empty($countField) && !empty($data[$countField]) && is_numeric($data[$countField])) {
            $count = min((int)$data[$countField], $maxCount);

            return $count;
        } else {
            return $maxCount;
        }
    }

    /**
     * after find entities
     *
     * @param Event $event An Event instance.
     * @return Entity
     */
    public function afterFind(Event $event)
    {
        $action = $event->getSubject();
        $records = $event->getData('records');
        $result = $action->getService()->getResult();

        $newMaxId = null;
        $newSinceId = null;
        $cursorField = $this->getConfig('cursorField');
        foreach ($records as $item) {
            $value = $item[$cursorField];
            if ($value !== null) {
                $newMaxId = is_null($newMaxId) ? $value : min($value, $newMaxId);
                $newSinceId = is_null($newSinceId) ? $value : max($value, $newSinceId);
            }
        }

        $sinceId = $this->_sinceId($action);
        $maxId = $this->_maxId($action);

        if ($newSinceId === null) {
            $newSinceId = $sinceId;
        }

        if ($newMaxId === null) {
            $newMaxId = $maxId;
        }

        $indexRoute = $action->getRoute();

        $links = [];
        $path = $this->_reverseRouter->indexPath($action, function ($route) use ($newSinceId) {
            $route['?']['since_id'] = $newSinceId;

            return $route;
        });
        $link = $this->_reverseRouter->link('prev', $path, $indexRoute['_method']);
        $links[$link['name']] = $link['href'];

        $path = $this->_reverseRouter->indexPath($action, function ($route) use ($newMaxId) {
            $route['?']['max_id'] = $newMaxId;

            return $route;
        });
        $link = $this->_reverseRouter->link('next', $path, $indexRoute['_method']);
        $links[$link['name']] = $link['href'];

        $count = $this->_count($action);
        $pagination = [
            'links' => $links,
            'count' => $count,
            'since_id' => $sinceId,
            'max_id' => $maxId,
        ];
        $result->setPayload('pagination', $pagination);
    }
}
