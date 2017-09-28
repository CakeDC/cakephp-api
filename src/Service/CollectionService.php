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

namespace CakeDC\Api\Service;

use CakeDC\Api\Routing\ApiRouter;
use Cake\Routing\RouteBuilder;
use CakeDC\Api\Service\Action\Collection\AddEditAction;

/**
 * Class CollectionService, manage the /collection endpoint to allow bulk operations
 *
 * @package CakeDC\Api\Service
 */
class CollectionService extends CrudService
{
    protected $_actionsClassMap = [
        'collectionAddEdit' => AddEditAction::class,
        'collectionDelete' => '\CakeDC\Api\Service\Action\Collection\DeleteAction',
    ];

    /**
     * Initialize service level routes
     *
     * @return void
     */
    public function loadRoutes()
    {
        ApiRouter::scope('/' . $this->getName(), function (RouteBuilder $routes) {
            $routes->extensions($this->_routeExtensions);
            $routes->connect('/collection/add', [
                'controller' => $this->getName(),
                'action' => 'collectionAddEdit',
            ]);
            $routes->connect('/collection/edit', [
                'controller' => $this->getName(),
                'action' => 'collectionAddEdit',
            ]);
            $routes->connect('/collection/delete', [
                'controller' => $this->getName(),
                'action' => 'collectionDelete',
            ]);
        });
    }
}
