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

namespace CakeDC\Api\Test\App\Service;

use CakeDC\Api\Service\FallbackService;
use CakeDC\Api\Test\App\DI\Service\TestService;

class ArticlesService extends FallbackService
{
    protected array $_actions = [
        'tag' => ['method' => ['PUT', 'POST'], 'path' => 'tag/{id}'],
        'untag' => ['method' => ['PUT', 'POST'], 'path' => 'untag/{id}'],
        'data' => ['method' => ['GET'], 'path' => 'data'],
    ];

    public function initialize(): void
    {
        parent::initialize();

        $this->mapAction('data', DataAction::class, [
            'method' => ['GET'],
            'mapCors' => true,
            'path' => 'data',
        ]);

        $this->setTable('Articles');

        if ($this->container !== null) {
            $testS = $this->container->get(TestService::class);
            assert($testS instanceof TestService);
        }
    }
}
