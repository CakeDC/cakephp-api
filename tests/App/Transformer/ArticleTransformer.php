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

namespace CakeDC\Api\Test\App\Transformer;

use CakeDC\Api\Transformer\AbstractTransformer;

/**
 * Transforms an Article entity into the API shape used by actions.
 */
class ArticleTransformer extends AbstractTransformer
{
    public function transform(mixed $data): array
    {
        return [
            'id' => $this->get($data, 'id'),
            'title' => $this->get($data, 'title'),
            'published' => $this->get($data, 'published') === 'Y',
        ];
    }
}
