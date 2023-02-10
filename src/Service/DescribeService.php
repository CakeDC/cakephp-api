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
    protected array $_actionsClassMap = [
        'describe' => \CakeDC\Api\Service\Action\DescribeAction::class,
    ];

    /**
     * @inheritDoc
     */
    public function loadRoutes(): void
    {
        $builder = ApiRouter::createRouteBuilder('/', []);
        $builder->scope('/', function (RouteBuilder $routes): void {
            $routes->setExtensions($this->_routeExtensions);
            $routes->connect('/describe/', ['controller' => 'describe', 'action' => 'describe']);
        });
    }
}
