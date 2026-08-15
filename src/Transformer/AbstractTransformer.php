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

use Cake\Datasource\EntityInterface;
use Cake\I18n\Date;
use Cake\I18n\DateTime;

/**
 * Abstract Transformer
 *
 * Base class for transformers with common helper methods.
 * Provides utilities for conditional fields, data access, date formatting,
 * and nested transformations.
 */
abstract class AbstractTransformer implements TransformerInterface
{
    /**
     * Conditionally include field
     *
     * @param bool $condition Condition to check
     * @param mixed $value Value to return if condition is true
     * @param mixed $default Default value if condition is false
     * @return mixed
     */
    protected function when(bool $condition, $value, $default = null): mixed
    {
        return $condition ? $value : $default;
    }

    /**
     * Get value from entity or array
     *
     * Works with both CakePHP entities and plain arrays.
     * Supports CakePHP matchingData and joinData arrays.
     *
     * @param mixed $data Entity or array
     * @param string $key Key to access
     * @param mixed $default Default value if key doesn't exist
     * @return mixed
     */
    protected function get($data, string $key, $default = null): mixed
    {
        if (is_array($data)) {
            return $data[$key] ?? $default;
        }

        if (is_object($data)) {
            if (isset($data->$key)) {
                return $data->$key;
            }

            if (method_exists($data, $key)) {
                return $data->$key();
            }

            if ($data instanceof EntityInterface && $data->has($key)) {
                return $data->get($key);
            }
        }

        return $default;
    }

    /**
     * Format date consistently
     *
     * @param mixed $date Date to format
     * @return string|null ISO 8601 formatted date string or null
     */
    protected function timestamp($date): ?string
    {
        if (!$date) {
            return null;
        }

        if ($date instanceof DateTime || $date instanceof Date) {
            return $date->toIso8601String();
        }

        if ($date instanceof \DateTime) {
            return $date->format('c');
        }

        if (is_string($date)) {
            try {
                return (new DateTime($date))->toIso8601String();
            } catch (\Exception) {
                return null;
            }
        }

        return null;
    }

    /**
     * Transform nested entity
     *
     * @param mixed $entity Entity or array to transform
     * @param string $transformerClass Transformer class name
     * @return array|null Transformed data or null if entity is empty
     */
    protected function item($entity, string $transformerClass): ?array
    {
        if (!$entity) {
            return null;
        }

        $transformer = new $transformerClass();

        return $transformer->transform($entity);
    }

    /**
     * Transform nested collection
     *
     * @param iterable $entities Collection of entities or arrays
     * @param string $transformerClass Transformer class name
     * @return array Transformed collection
     */
    protected function collection($entities, string $transformerClass): array
    {
        if (!$entities) {
            return [];
        }

        $transformer = new $transformerClass();
        $result = [];

        foreach ($entities as $entity) {
            $result[] = $transformer->transform($entity);
        }

        return $result;
    }

    /**
     * Transform matchingData array (CakePHP association matching)
     *
     * @param array|null $matchingData Matching data array
     * @param string $transformerClass Transformer class name
     * @return array|null Transformed matching data or null
     */
    protected function matchingData(?array $matchingData, string $transformerClass): ?array
    {
        if (!$matchingData) {
            return null;
        }

        $transformer = new $transformerClass();

        return $transformer->transform($matchingData);
    }

    /**
     * Transform joinData array (CakePHP association join data)
     *
     * @param array|null $joinData Join data array
     * @param string $transformerClass Transformer class name
     * @return array|null Transformed join data or null
     */
    protected function joinData(?array $joinData, string $transformerClass): ?array
    {
        if (!$joinData) {
            return null;
        }

        $transformer = new $transformerClass();

        return $transformer->transform($joinData);
    }
}
