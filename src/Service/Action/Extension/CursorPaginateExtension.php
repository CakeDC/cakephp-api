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
use Cake\Event\EventInterface;
use Cake\Event\EventListenerInterface;
use Cake\ORM\Entity;
use CakeDC\Api\Service\Action\Action;
use CakeDC\Api\Service\Action\ExtensionRegistry;
use CakeDC\Api\Service\Utility\ReverseRouting;

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

    protected \CakeDC\Api\Service\Utility\ReverseRouting $_reverseRouter;

    /**
     * CursorPaginateExtension constructor.
     *
     * @param \CakeDC\Api\Service\Action\ExtensionRegistry $registry An Extension Registry instance.
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
    public function implementedEvents(): array
    {
        return [
            'Action.Crud.onFindEntities' => 'findEntities',
            'Action.Crud.afterFindEntities' => 'afterFind',
        ];
    }

    /**
     * find entities
     *
     * @param \Cake\Event\Event $event An Event instance.
     * @return \Cake\ORM\Query
     */
    public function findEntities(Event $event): \Cake\ORM\Query
    {
        /** @var \CakeDC\Api\Service\Action\Action $action */
        $action = $event->getSubject();
        /** @var \Cake\ORM\Query $query */
        $query = $event->getData('query');
        if ($event->getResult()) {
            $query = $event->getResult();
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
     * @param \CakeDC\Api\Service\Action\Action $action An Action instance.
     * @return int|null
     */
    protected function _sinceId(Action $action): ?int
    {
        $data = $action->getData();
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
     * @param \CakeDC\Api\Service\Action\Action $action An Action instance.
     * @return int|null
     */
    protected function _maxId(Action $action): ?int
    {
        $data = $action->getData();
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
     * @param \CakeDC\Api\Service\Action\Action $action An Action instance.
     * @return int|null
     */
    protected function _count(Action $action): ?int
    {
        $data = $action->getData();
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
     * @param \Cake\Event\EventInterface $event An Event instance.
     * @return \Cake\ORM\Entity|null
     */
    public function afterFind(EventInterface $event): ?Entity
    {
        /** @var \CakeDC\Api\Service\Action\Action $action */
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
        $version = $action->getService()->getVersion();

        $links = [];
        $path = $this->_reverseRouter->indexPath($action, function ($route) use ($newSinceId) {
            $route['?']['since_id'] = $newSinceId;

            return $route;
        });
        $link = $this->_reverseRouter->link('prev', $path, $indexRoute['_method'], $version);
        $links[$link['name']] = $link['href'];

        $path = $this->_reverseRouter->indexPath($action, function ($route) use ($newMaxId) {
            $route['?']['max_id'] = $newMaxId;

            return $route;
        });
        $link = $this->_reverseRouter->link('next', $path, $indexRoute['_method'], $version);
        $links[$link['name']] = $link['href'];

        $count = $this->_count($action);
        $pagination = [
            'links' => $links,
            'count' => $count,
            'since_id' => $sinceId,
            'max_id' => $maxId,
        ];
        $result->appendPayload('pagination', $pagination);

        return null;
    }
}
