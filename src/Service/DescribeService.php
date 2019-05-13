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

namespace CakeDC\Api\Service;

use Cake\Routing\RouteBuilder;
use CakeDC\Api\Routing\ApiRouter;

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
