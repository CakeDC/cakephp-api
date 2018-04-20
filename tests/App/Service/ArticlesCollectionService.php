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

namespace CakeDC\Api\Test\App\Service;

use CakeDC\Api\Service\Action\Collection\AddEditAction;
use CakeDC\Api\Service\Action\Collection\DeleteAction;
use CakeDC\Api\Service\CollectionService;
use CakeDC\Api\Service\FallbackService;

class ArticlesCollectionService extends FallbackService
{
    public function initialize()
    {
        parent::initialize();

        $this->mapAction('collectionAdd', AddEditAction::class, [
            'method' => ['POST'],
            'mapCors' => true,
            'path' => 'collection/add'
        ]);
        $this->mapAction('collectionEdit', AddEditAction::class, [
            'method' => ['POST'],
            'mapCors' => true,
            'path' => 'collection/edit'
        ]);
        $this->mapAction('collectionDelete', DeleteAction::class, [
            'method' => ['POST'],
            'mapCors' => true,
            'path' => 'collection/delete'
        ]);

        $this->setTable('Articles');
    }
}
