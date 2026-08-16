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

namespace CakeDC\Api\Test\App\Service\Action;

use CakeDC\Api\Service\Action\CrudAction;
use CakeDC\Api\Test\App\Transformer\ArticleTransformer;

/**
 * Lists published articles, transforming each entity lazily via the transformer.
 */
class FeaturedAction extends CrudAction
{
    public function execute(): mixed
    {
        $transformer = new ArticleTransformer();

        return $this->getTable()
            ->find()
            ->where(['published' => 'Y'])
            ->all()
            ->map(fn($article): array => $transformer->transform($article));
    }
}
