<?php
/**
 * Copyright 2016 - 2018, Cake Development Corporation (http://cakedc.com)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright 2016 - 2018, Cake Development Corporation (http://cakedc.com)
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

namespace CakeDC\Api\Service;

use CakeDC\Api\Routing\ApiRouter;
use Cake\Routing\RouteBuilder;

class DescribeService extends Service
{

    /**
     * @var array
     */
    protected $_actionsClassMap = [
        'describe' => '\CakeDC\Api\Service\Action\DescribeAction',
    ];

    /**
     * @inheritdoc
     *
     * @return void
     */
    public function loadRoutes()
    {
        ApiRouter::scope('/', function (RouteBuilder $routes) {
            $routes->setExtensions($this->_routeExtensions);
            $routes->connect('/describe/', ['controller' => 'describe', 'action' => 'describe']);
        });
    }
}
