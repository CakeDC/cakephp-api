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

namespace CakeDC\Api\Test\TestCase\Transformer;

use CakeDC\Api\Transformer\AbstractTransformer;

class TestTransformer extends AbstractTransformer
{
    public function transform(mixed $data): array
    {
        return ['id' => $this->get($data, 'id')];
    }

    public function exposedWhen(bool $condition, $value, $default = null): mixed
    {
        return $this->when($condition, $value, $default);
    }

    public function exposedGet($data, string $key, $default = null): mixed
    {
        return $this->get($data, $key, $default);
    }

    public function exposedTimestamp($date): ?string
    {
        return $this->timestamp($date);
    }

    public function exposedItem($entity, string $transformerClass): ?array
    {
        return $this->item($entity, $transformerClass);
    }

    public function exposedCollection($entities, string $transformerClass): array
    {
        return $this->collection($entities, $transformerClass);
    }

    public function exposedMatchingData(?array $matchingData, string $transformerClass): ?array
    {
        return $this->matchingData($matchingData, $transformerClass);
    }

    public function exposedJoinData(?array $joinData, string $transformerClass): ?array
    {
        return $this->joinData($joinData, $transformerClass);
    }
}
