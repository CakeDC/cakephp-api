<?php
declare(strict_types=1);

/**
 * Copyright 2016 - 2026, Cake Development Corporation (http://cakedc.com)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright 2016 - 2026, Cake Development Corporation (http://cakedc.com)
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

namespace CakeDC\Api\Test\App\Service;

use CakeDC\Api\Service\Action\CrudIndexAction;
use CakeDC\Api\Service\FallbackService;

/**
 * Posts service exercising custom route path aliases on top of the
 * generic nested CRUD from FallbackService.
 */
class PostsService extends FallbackService
{
    /**
     * Initialize method
     *
     * @return void
     */
    #[\Override]
    public function initialize(): void
    {
        parent::initialize();

        // Route alias: GET /posts/featured -> generic index action
        $this->mapAction('featured', CrudIndexAction::class, [
            'method' => ['GET'],
            'path' => 'featured',
        ]);

        // Route alias with a path parameter: GET /posts/category/{category}
        $this->mapAction('category', CrudIndexAction::class, [
            'method' => ['GET'],
            'path' => 'category/{category}',
        ]);
    }
}
