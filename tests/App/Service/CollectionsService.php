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

use CakeDC\Api\Service\Action\Collection\AddEditAction;
use CakeDC\Api\Service\Action\Collection\DeleteAction;
use CakeDC\Api\Service\FallbackService;

/**
 * Single-word collection service: exercises the bulk AddEdit/Delete
 * actions through real HTTP at /collections/collection/{add,edit,delete}.
 */
class CollectionsService extends FallbackService
{
    /**
     * Initialize method
     *
     * @return void
     */
    public function initialize(): void
    {
        parent::initialize();

        $this->mapAction('collectionAdd', AddEditAction::class, [
            'method' => ['POST'],
            'mapCors' => true,
            'path' => 'collection/add',
        ]);
        $this->mapAction('collectionEdit', AddEditAction::class, [
            'method' => ['POST'],
            'mapCors' => true,
            'path' => 'collection/edit',
        ]);
        $this->mapAction('collectionDelete', DeleteAction::class, [
            'method' => ['POST'],
            'mapCors' => true,
            'path' => 'collection/delete',
        ]);
    }

    /**
     * Gets the table name.
     *
     * @return string
     */
    public function getTable(): string
    {
        return 'Articles';
    }
}
