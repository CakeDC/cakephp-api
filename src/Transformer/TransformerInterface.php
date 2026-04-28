<?php
declare(strict_types=1);

/**
 * Copyright 2016 - 2025, Cake Development Corporation (http://cakedc.com)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright 2016 - 2025, Cake Development Corporation (http://cakedc.com)
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

namespace CakeDC\Api\Transformer;

/**
 * Transformer Interface
 *
 * Defines the contract for transforming data for API responses.
 * Transformers handle the conversion of entities, arrays, and collections
 * into API response format.
 */
interface TransformerInterface
{
    /**
     * Transform a single item (entity or array) to API response format
     *
     * @param mixed $data Entity or array to transform
     * @return array Transformed data
     */
    public function transform(mixed $data): array;
}

