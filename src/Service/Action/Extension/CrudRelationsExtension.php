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

use ArrayAccess;
use Authorization\AuthorizationServiceInterface;
use Authorization\IdentityDecorator;
use Authorization\IdentityInterface;
use Authorization\Policy\Exception\MissingMethodException;
use Authorization\Policy\Exception\MissingPolicyException;
use Cake\Event\Event;
use Cake\Event\EventListenerInterface;
use Cake\Log\Log;
use Cake\ORM\Association;
use Cake\ORM\Query;
use Cake\ORM\Table;
use Cake\Utility\Inflector;
use CakeDC\Api\Rbac\ApiRbac;
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
     * Default settings
     *
     * - `checkPermissions`: embed an association only when the user may access its service.
     *   Set it to false to embed requested associations without that check, as before.
     *
     * @var array
     */
    protected $_defaultConfig = [
        'checkPermissions' => true,
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
        /** @var \CakeDC\Api\Service\Action\CrudAction $action */
        $action = $event->getSubject();
        /** @var \Cake\ORM\Query $query */
        $query = $event->getData('query');

        return $this->_attachAssociations($action, $query);
    }

    /**
     * On find entities.
     *
     * @param \Cake\Event\Event $event An Event instance.
     * @return \Cake\ORM\Query
     */
    public function findEntities(Event $event): \Cake\ORM\Query
    {
        /** @var \CakeDC\Api\Service\Action\CrudAction $action */
        $action = $event->getSubject();
        /** @var \Cake\ORM\Query $query */
        $query = $event->getData('query');

        return $this->_attachAssociations($action, $query);
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
                ->map(fn(Association $relation) => $relation->getTarget()->getTable())
                ->toArray();
        }
        if (empty($associations)) {
            return $query;
        }

        $tables = collection($associations)
            ->map(fn($name) => Inflector::camelize($name))
            ->toArray();
        // embed only the associations whose service the current user may access
        $tables = array_values(array_filter($tables, function ($name) use ($action) {
            return $this->_isRelationAccessible($action, (string)$name);
        }));
        if ($tables === []) {
            return $query;
        }

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

    /**
     * Whether the current user may access the service of every table on an association path.
     *
     * The data of an association would otherwise be embedded without the permissions of its own
     * service. The request's authorization service is asked whether the user may access the
     * `index` action of each related service, named after the association's table alias. Without
     * an authorization service, without a policy for requests, or with `checkPermissions` set to
     * false, the association is embedded as before.
     *
     * @param \CakeDC\Api\Service\Action\CrudAction $action Action instance.
     * @param string $path Association name, or a dotted path of association names.
     * @return bool
     */
    protected function _isRelationAccessible(CrudAction $action, string $path): bool
    {
        if (!$this->getConfig('checkPermissions')) {
            return true;
        }
        // a nested service keeps the request from before authorization; the root service has the current one
        $service = $action->getService();
        while ($service->getParentService() !== null) {
            $service = $service->getParentService();
        }
        $request = $service->getRequest();
        $authorization = $request === null ? null : $request->getAttribute('authorization');
        if (!$authorization instanceof AuthorizationServiceInterface) {
            Log::debug(sprintf('CrudRelations embeds %s without a permission check: no authorization service', $path), [
                'scope' => ['api'],
            ]);

            return true;
        }
        // RbacPolicy prefers the request's `rbac` adapter; only ApiRbac knows the target to check
        $rbac = $request->getAttribute('rbac');
        if ($rbac !== null && !$rbac instanceof ApiRbac) {
            Log::debug(sprintf('CrudRelations checks %s with an RBAC adapter that may ignore the target', $path), [
                'scope' => ['api'],
            ]);
        }
        $identity = $request->getAttribute('identity');
        if ($identity !== null && !$identity instanceof IdentityInterface) {
            $identity = is_array($identity) || $identity instanceof ArrayAccess
                ? new IdentityDecorator($authorization, $identity)
                : null;
        }

        $table = $action->getTable();
        foreach (explode('.', $path) as $name) {
            $association = $table->associations()->get($name);
            if ($association === null) {
                // an unknown association fails later, as before
                return true;
            }
            $table = $association->getTarget();
            // the data belongs to the service of the target table, also for an aliased association
            // such as `Writers` with `className` `Authors`
            $serviceName = $this->_relatedServiceName($table);
            $spellings = array_unique([Inflector::underscore($serviceName), Inflector::dasherize($serviceName)]);
            $probe = $request->withMethod('GET')->withAttribute(ApiRbac::TARGET_ATTRIBUTE, [
                'service' => array_values($spellings),
                'action' => 'index',
            ]);
            try {
                if (!$authorization->can($identity, 'access', $probe)) {
                    return false;
                }
            } catch (MissingPolicyException | MissingMethodException $exception) {
                // no request policy with an `access` check: keep the association, as before
                Log::debug(sprintf('CrudRelations embeds %s without a permission check: no request policy', $path), [
                    'scope' => ['api'],
                ]);

                return true;
            }
        }

        return true;
    }

    /**
     * Returns the name of the service that owns the data of a table.
     *
     * A service finds its table by its own name, so the table class names the service: `AuthorsTable`
     * gives `Authors`, whatever the association alias or the database table name. A generic `Table`
     * has no class of its own, so its database table names the service.
     *
     * @param \Cake\ORM\Table $table Target table of an association.
     * @return string
     */
    protected function _relatedServiceName(Table $table): string
    {
        [, $class] = namespaceSplit(get_class($table));
        if ($class !== 'Table' && substr($class, -5) === 'Table') {
            return substr($class, 0, -5);
        }
        $name = explode('.', $table->getTable());

        return Inflector::camelize(end($name));
    }
}
