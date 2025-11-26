# Transformers

Transformers provide a clean, consistent way to transform entities, arrays, and collections for API responses. They separate presentation logic from business logic, making your API responses maintainable and testable.

## Overview

The transformer layer allows you to:
- Transform entities and arrays consistently
- Handle collections and paginated results
- Support nested associations (using CakePHP `contain()`)
- Transform CakePHP `matchingData` and `joinData` arrays
- Conditionally include fields based on context
- Format dates and other data types consistently

## Basic Usage

### Creating a Transformer

Create a transformer class that extends `AbstractTransformer`:

```php
<?php
namespace App\Transformer;

use CakeDC\Api\Transformer\AbstractTransformer;

class UserTransformer extends AbstractTransformer
{
    public function transform($data): array
    {
        return [
            'id' => (int)$this->get($data, 'id'),
            'username' => $this->get($data, 'username'),
            'email' => $this->get($data, 'email'),
            'created' => $this->timestamp($this->get($data, 'created')),
        ];
    }
}
```

### Using in Actions

Use the `transform()` method in your actions:

```php
<?php
namespace App\Service\AppUsers;

use App\Transformer\UserTransformer;
use CakeDC\Api\Service\Action\CrudAction;

class ViewAction extends CrudAction
{
    public function execute()
    {
        $user = $this->_getEntity($this->_id);

        return $this->transform($user, UserTransformer::class);
    }
}
```

### Transforming Collections

The `transform()` method automatically handles collections:

```php
<?php
namespace App\Service\AppUsers;

use App\Transformer\UserTransformer;
use CakeDC\Api\Service\Action\CrudIndexAction;

class IndexAction extends CrudIndexAction
{
    public function execute()
    {
        $users = $this->_getEntities();

        return $this->transform($users, UserTransformer::class);
    }
}
```

## Helper Methods

### `get($data, $key, $default = null)`

Unified data access that works with both entities and arrays:

```php
// Works with entities
$id = $this->get($user, 'id');

// Works with arrays
$id = $this->get($arrayData, 'id');

// Works with CakePHP entities
$email = $this->get($entity, 'email', 'no-email@example.com');
```

### `when($condition, $value, $default = null)`

Conditionally include fields:

```php
public function transform($data): array
{
    return [
        'id' => $this->get($data, 'id'),
        'email' => $this->when(
            $this->get($data, 'is_email_visible', true),
            $this->get($data, 'email')
        ),
    ];
}
```

### `timestamp($date)`

Format dates consistently to ISO 8601:

```php
public function transform($data): array
{
    return [
        'created' => $this->timestamp($this->get($data, 'created')),
        'updated' => $this->timestamp($this->get($data, 'updated')),
    ];
}
```

### `item($entity, $transformerClass)`

Transform nested entities:

```php
public function transform($data): array
{
    $courseType = $this->get($data, 'course_type');

    return [
        'id' => $this->get($data, 'id'),
        'course_type' => $courseType
            ? $this->item($courseType, CourseTypeTransformer::class)
            : null,
    ];
}
```

### `collection($entities, $transformerClass)`

Transform nested collections:

```php
public function transform($data): array
{
    $posts = $this->get($data, 'posts');

    return [
        'id' => $this->get($data, 'id'),
        'posts' => $this->collection($posts, PostTransformer::class),
    ];
}
```

### `matchingData($matchingData, $transformerClass)`

Transform CakePHP `matchingData` arrays:

```php
public function transform($data): array
{
    $matchingData = $this->get($data, '_matchingData');

    return [
        'id' => $this->get($data, 'id'),
        'enrollment_data' => isset($matchingData['Enrollments'])
            ? $this->matchingData($matchingData['Enrollments'], EnrollmentTransformer::class)
            : null,
    ];
}
```

### `joinData($joinData, $transformerClass)`

Transform CakePHP `joinData` arrays:

```php
public function transform($data): array
{
    $joinData = $this->get($data, '_joinData');

    return [
        'id' => $this->get($data, 'id'),
        'pivot_data' => $joinData
            ? $this->joinData($joinData, PivotTransformer::class)
            : null,
    ];
}
```

## Advanced Examples

### Nested Associations

Transform entities with nested associations loaded via `contain()`:

```php
<?php
namespace App\Transformer;

use CakeDC\Api\Transformer\AbstractTransformer;

class CourseTransformer extends AbstractTransformer
{
    public function transform($data): array
    {
        $courseType = $this->get($data, 'course_type');
        $location = $this->get($data, 'location');

        return [
            'id' => (int)$this->get($data, 'id'),
            'course_number' => $this->get($data, 'course_number'),
            'start_date' => $this->timestamp($this->get($data, 'start_date')),
            'end_date' => $this->timestamp($this->get($data, 'end_date')),
            'course_type' => $courseType
                ? $this->item($courseType, CourseTypeTransformer::class)
                : null,
            'location' => $location
                ? $this->item($location, LocationTransformer::class)
                : null,
        ];
    }
}
```

### Array Data (JOIN Results)

Transform raw arrays from complex JOIN queries:

```php
<?php
namespace App\Transformer;

use CakeDC\Api\Transformer\AbstractTransformer;

class UserStatsTransformer extends AbstractTransformer
{
    public function transform($data): array
    {
        return [
            'user_id' => (int)$this->get($data, 'user_id'),
            'username' => $this->get($data, 'username'),
            'post_count' => (int)$this->get($data, 'post_count', 0),
            'comment_count' => (int)$this->get($data, 'comment_count', 0),
            'last_active' => $this->timestamp($this->get($data, 'last_active')),
        ];
    }
}
```

### Conditional Fields with Context

Use context for conditional field inclusion:

```php
<?php
namespace App\Transformer;

use CakeDC\Api\Transformer\AbstractTransformer;

class SecureUserTransformer extends AbstractTransformer
{
    protected $context = [];

    public function setContext(array $context): void
    {
        $this->context = $context;
    }

    public function transform($data): array
    {
        $isAdmin = $this->context['is_admin'] ?? false;
        $authUserId = $this->context['auth_user_id'] ?? null;
        $isOwner = $authUserId === $this->get($data, 'id');

        return [
            'id' => (int)$this->get($data, 'id'),
            'username' => $this->get($data, 'username'),
            'email' => $this->when($isOwner, $this->get($data, 'email')),
            'private_data' => $this->when($isAdmin, $this->get($data, 'private_data')),
        ];
    }
}
```

Usage with context:

```php
<?php
namespace App\Service\AppUsers;

use App\Transformer\SecureUserTransformer;
use CakeDC\Api\Service\Action\CrudAction;

class ViewAction extends CrudAction
{
    public function execute()
    {
        $user = $this->_getEntity($this->_id);
        $identity = $this->getIdentity();

        $transformer = new SecureUserTransformer();
        $transformer->setContext([
            'is_admin' => $identity->is_admin ?? false,
            'auth_user_id' => $identity->id ?? null,
        ]);

        return $transformer->transform($user);
    }
}
```

## Paginated Results

The `transform()` method automatically preserves pagination metadata:

```php
<?php
namespace App\Service\AppUsers;

use App\Transformer\UserTransformer;
use CakeDC\Api\Service\Action\CrudIndexAction;

class IndexAction extends CrudIndexAction
{
    public function execute()
    {
        // If pagination extension is enabled, result will be:
        // ['data' => [...], 'pagination' => [...]]
        $result = $this->_getEntities();

        // transform() preserves pagination structure
        return $this->transform($result, UserTransformer::class);
    }
}
```

## Best Practices

1. **Keep transformers focused**: Each transformer should handle one entity type
2. **Use helper methods**: Leverage `get()`, `when()`, `timestamp()` for consistency
3. **Handle null values**: Always check for null before transforming nested entities
4. **Use context for conditional fields**: Pass context when you need role-based or user-based field inclusion
5. **Transform at the action level**: Use `$this->transform()` in actions, not in services
6. **Test transformers**: Unit test transformers independently from actions

## Interface

All transformers must implement `TransformerInterface`:

```php
interface TransformerInterface
{
    public function transform($data): array;
}
```

The `AbstractTransformer` base class provides common helper methods and implements this interface.

