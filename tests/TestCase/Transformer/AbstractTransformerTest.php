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

use Cake\I18n\DateTime;
use Cake\ORM\Entity;
use CakeDC\Api\TestSuite\TestCase;

/**
 * Unit tests for the transformer helper methods.
 */
class AbstractTransformerTest extends TestCase
{
    public $transformer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->transformer = new TestTransformer();
    }

    public function testWhenTrue(): void
    {
        $this->assertSame('value', $this->transformer->exposedWhen(true, 'value'));
    }

    public function testWhenFalse(): void
    {
        $this->assertSame('default', $this->transformer->exposedWhen(false, 'value', 'default'));
        $this->assertNull($this->transformer->exposedWhen(false, 'value'));
    }

    public function testGetFromArray(): void
    {
        $this->assertSame(5, $this->transformer->exposedGet(['id' => 5], 'id'));
        $this->assertNull($this->transformer->exposedGet(['id' => 5], 'missing'));
        $this->assertSame('d', $this->transformer->exposedGet(['id' => 5], 'missing', 'd'));
    }

    public function testGetFromObjectProperty(): void
    {
        $obj = new \stdClass();
        $obj->title = 'Hello';
        $this->assertSame('Hello', $this->transformer->exposedGet($obj, 'title'));
    }

    public function testGetFromObjectMethod(): void
    {
        $obj = new class {
            public function name(): string
            {
                return 'method-result';
            }
        };
        $this->assertSame('method-result', $this->transformer->exposedGet($obj, 'name'));
    }

    public function testGetFromEntity(): void
    {
        $entity = new Entity(['id' => 42, 'title' => 'Post']);
        $this->assertSame(42, $this->transformer->exposedGet($entity, 'id'));
        $this->assertNull($this->transformer->exposedGet($entity, 'missing'));
    }

    public function testTimestampFalsyReturnsNull(): void
    {
        $this->assertNull($this->transformer->exposedTimestamp(null));
        $this->assertNull($this->transformer->exposedTimestamp(''));
        $this->assertNull($this->transformer->exposedTimestamp(0));
    }

    public function testTimestampCakeDateTime(): void
    {
        $date = new DateTime('2024-01-15 10:00:00');
        $result = $this->transformer->exposedTimestamp($date);
        $this->assertIsString($result);
        $this->assertStringStartsWith('2024-01-15T10:00:00', $result);
    }

    public function testTimestampPhpDateTime(): void
    {
        $date = new \DateTime('2024-02-20 08:30:00');
        $result = $this->transformer->exposedTimestamp($date);
        $this->assertIsString($result);
        $this->assertStringStartsWith('2024-02-20T08:30:00', $result);
    }

    public function testTimestampStringDate(): void
    {
        $result = $this->transformer->exposedTimestamp('2024-03-01 12:00:00');
        $this->assertIsString($result);
        $this->assertStringStartsWith('2024-03-01', $result);
    }

    public function testTimestampInvalidStringReturnsNull(): void
    {
        $this->assertNull($this->transformer->exposedTimestamp('not-a-date'));
    }

    public function testItem(): void
    {
        $result = $this->transformer->exposedItem(['id' => 7], TestTransformer::class);
        $this->assertSame(['id' => 7], $result);
    }

    public function testItemEmptyReturnsNull(): void
    {
        $this->assertNull($this->transformer->exposedItem(null, TestTransformer::class));
    }

    public function testCollection(): void
    {
        $result = $this->transformer->exposedCollection([['id' => 1], ['id' => 2]], TestTransformer::class);
        $this->assertSame([['id' => 1], ['id' => 2]], $result);
    }

    public function testCollectionEmptyReturnsEmptyArray(): void
    {
        $this->assertSame([], $this->transformer->exposedCollection([], TestTransformer::class));
    }

    public function testMatchingData(): void
    {
        $result = $this->transformer->exposedMatchingData(['id' => 3], TestTransformer::class);
        $this->assertSame(['id' => 3], $result);
        $this->assertNull($this->transformer->exposedMatchingData(null, TestTransformer::class));
    }

    public function testJoinData(): void
    {
        $result = $this->transformer->exposedJoinData(['id' => 2], TestTransformer::class);
        $this->assertSame(['id' => 2], $result);
        $this->assertNull($this->transformer->exposedJoinData(null, TestTransformer::class));
    }

    public function testTransformContract(): void
    {
        $this->assertSame(['id' => 9], $this->transformer->transform(['id' => 9]));
    }
}
