# Attribute Routing for Services

- [Introduction](#introduction)
- [How It Works](#how-it-works)
- [Service-Level Attributes](#service-level-attributes)
    - [ApiScope](#apiscope)
    - [ApiResource](#apiresource)
- [Declaring Actions](#declaring-actions)
    - [ApiActions on the Service](#apiactions-on-the-service)
    - [ApiRoute on Action Classes](#apiroute-on-action-classes)
    - [HTTP Method Shortcuts](#http-method-shortcuts)
- [Route Parameters](#route-parameters)
    - [Placeholders](#placeholders)
    - [Patterns](#patterns)
    - [Pass](#pass)
    - [CORS Preflight](#cors-preflight)
- [Nesting](#nesting)
- [Inheritance](#inheritance)
- [Caching](#caching)
- [Attribute Reference](#attribute-reference)

<a name="introduction"></a>
## Introduction

Service routes can be declared with PHP attributes. Following the CakePHP 6
attribute-routing model, each **action class declares its own route**, and the
service lists which actions it owns. Discovery and caching are backed by the
[`crustum/cakephp-attribute-resolver`](https://packagist.org/packages/crustum/cakephp-attribute-resolver)
package (a CakePHP 5.4 backport of `Cake\AttributeResolver`).

A typical `Service::mapAction()` block:

```php
$this->mapAction('featured', FeaturedAction::class, [
    'method' => ['GET'],
    'mapCors' => true,
    'path' => '/featured',
]);
$this->mapAction('publish', PublishAction::class, [
    'method' => ['POST'],
    'mapCors' => true,
    'path' => '/{id}/publish',
]);
```

becomes:

```php
// src/Service/ArticlesService.php
use App\Service\Action\FeaturedAction;
use App\Service\Action\PublishAction;
use CakeDC\Api\Service\Attribute\ApiActions;
use CakeDC\Api\Service\FallbackService;

#[ApiActions([
    FeaturedAction::class,
    PublishAction::class,
])]
class ArticlesService extends FallbackService
{
}
```

```php
// src/Service/Action/FeaturedAction.php
use CakeDC\Api\Service\Action\CrudAction;
use CakeDC\Api\Service\Attribute\ApiGet;

#[ApiGet(action: 'featured', path: '/featured', mapCors: true)]
class FeaturedAction extends CrudAction
{
    public function execute(): mixed
    {
        // ...
    }
}
```

```php
// src/Service/Action/PublishAction.php
use CakeDC\Api\Service\Action\CrudAction;
use CakeDC\Api\Service\Attribute\ApiPost;

#[ApiPost(action: 'publish', path: '/{id}/publish', mapCors: true)]
class PublishAction extends CrudAction
{
    public function execute(): mixed
    {
        // $this->request->getParam('id')
    }
}
```

> [!NOTE]
> The `/api` base path and the version prefix are **config-driven**
> (`Api.routeBase`, `Api.useVersioning`, `Api.versionPrefix`). Attribute paths are
> relative to the service resource and must not include `/api` or a version segment.

Attribute routing is **fully optional** and composes with the existing actions map.

<a name="how-it-works"></a>
## How It Works

1. When a service is constructed, `Service::initialize()` runs the
   `ServiceAttributeConnector`.
2. The connector reads `ApiScope` / `ApiResource` / `ApiActions` from the service
   class (and its parents).
3. For every action class listed in `ApiActions`, it reads the action's route
   attributes and calls `Service::mapAction()` — exactly like the config-based
   registration above.
4. The existing `ApiRouter` builds the actual routes from the actions map, so
   attribute routes produce the same routes as config-based ones.

Attribute routing is **opt-in**: the connector is a no-op until a resolver
configuration exists (see [Caching](#caching)).

<a name="service-level-attributes"></a>
## Service-Level Attributes

<a name="apiscope"></a>
### ApiScope

`#[ApiScope]` sets a path prefix, shared defaults, and shared patterns for all
routes of the service's actions. Repeatable and stacked across inheritance:

```php
use CakeDC\Api\Service\Attribute\ApiScope;

#[ApiScope('/v1')]
#[ApiActions([FeaturedAction::class])]
class ArticlesService extends FallbackService
{
}
```

| Parameter | Type | Description |
|---|---|---|
| `path` | `string` | Path prefix prepended to every route path. |
| `defaults` | `array` | Default route values merged into every route. |
| `patterns` | `array` | Shared regex patterns for placeholders. |

> [!TIP]
> `ApiScope::path` only adds a sub-path **within** the service resource
> (`/api/{service}/{scope}/{action}`). Use it for grouping actions, not for the
> API or version base — those come from `Api.routeBase` / `Api.useVersioning`.

<a name="apiresource"></a>
### ApiResource

`#[ApiResource]` configures the REST resource routes generated for the service
(equivalent to options passed to `$routes->resources()`):

```php
use CakeDC\Api\Service\Attribute\ApiResource;

#[ApiResource(only: ['index', 'view', 'featured'])]
class ArticlesService extends FallbackService
{
}
```

| Parameter | Type | Description |
|---|---|---|
| `path` | `string\|null` | Override the resource URL path. |
| `only` | `array` | Limit which routes are generated (index/view/add/edit/delete + custom). |
| `actions` | `array` | Map REST actions to custom action classes. |
| `map` | `array` | Additional non-standard resource routes. |
| `id` | `string` | Regex pattern for the resource identifier. |

> [!WARNING]
> `only` filters **all** resource routes, including actions declared via
> `ApiActions`. List every custom action name in `only` when using both.

<a name="declaring-actions"></a>
## Declaring Actions

<a name="apiactions-on-the-service"></a>
### ApiActions on the Service

`#[ApiActions]` lists the action classes owned by a service. It is the attribute
equivalent of the list of `mapAction()` calls:

```php
use App\Service\Action\FeaturedAction;
use App\Service\Action\IndexAction;
use App\Service\Action\PublishAction;
use CakeDC\Api\Service\Attribute\ApiActions;
use CakeDC\Api\Service\FallbackService;

#[ApiActions([
    FeaturedAction::class,
    PublishAction::class,
    IndexAction::class,
])]
class ArticlesService extends FallbackService
{
}
```

Pass the action classes as an **array**. Each listed action class must carry at
least one route attribute.

<a name="apiroute-on-action-classes"></a>
### ApiRoute on Action Classes

`#[ApiRoute]` declares one route of an action class. Repeatable — one action can
serve multiple routes:

```php
use CakeDC\Api\Service\Attribute\ApiRoute;

#[ApiRoute(action: 'search', path: '/search', method: ['GET', 'POST'])]
class SearchAction extends CrudAction
{
}
```

| Parameter | Type | Description |
|---|---|---|
| `action` | `string` | Action name (route key, e.g. `save_answer`). |
| `path` | `string` | Route path, relative to the service resource. Supports `{placeholder}` params. |
| `method` | `string\|array` | HTTP method(s). Default: `GET`. |
| `mapCors` | `bool` | Register an OPTIONS route for CORS preflight. |
| `name` | `string\|null` | Optional route name. |
| `patterns` | `array` | Regex patterns for route placeholders. |
| `defaults` | `array` | Additional route defaults. |
| `pass` | `array\|null` | Placeholder names passed to the action. |

<a name="http-method-shortcuts"></a>
### HTTP Method Shortcuts

| Attribute | HTTP Method |
|---|---|
| `#[ApiGet]` | GET |
| `#[ApiPost]` | POST |
| `#[ApiPut]` | PUT |
| `#[ApiPatch]` | PATCH |
| `#[ApiDelete]` | DELETE |
| `#[ApiOptions]` | OPTIONS |

These accept the same parameters as `#[ApiRoute]` except `method`.

<a name="route-parameters"></a>
## Route Parameters

<a name="placeholders"></a>
### Placeholders

`{placeholder}` segments in `path` become route params, read inside the action
via `$this->request->getParam('name')` (or the `id` param for `CrudAction`):

```php
#[ApiGet(action: 'view', path: '/{id}')]
class ViewAction extends CrudAction
{
    public function execute(): mixed
    {
        $id = $this->request->getParam('id');
    }
}
```

```
GET /api/articles/42   -> id=42
```

<a name="patterns"></a>
### Patterns

Constrain placeholders with regex:

```php
#[ApiGet(action: 'item', path: '/item/{id}', patterns: ['id' => '\d+'])]
```

Shared patterns can be set at the service level via `ApiScope`.

<a name="pass"></a>
### Pass

`pass` controls which placeholders are passed to the action as positional
arguments. When omitted, placeholders are available as route params:

```php
#[ApiGet(action: 'view', path: '/{id}', pass: ['id'])]
```

<a name="cors-preflight"></a>
### CORS Preflight

`mapCors: true` registers an OPTIONS counterpart route for the action, mirroring
`'mapCors' => true` in `mapAction()`.

<a name="nesting"></a>
## Nesting

Attribute routing is **orthogonal** to nesting. Nested resources keep using the
existing mechanism (`FallbackService::loadRoutes()` walks the parent table's
`HasMany` associations, registers the nested routes, and the child service is
resolved through the parent and scoped by the parent foreign key).

A child service can mix attribute routes with nesting freely — its action routes
are reachable under the parent's nested path:

```
# AuthorsService hasMany Articles
GET /api/authors/1/articles/featured   # child service attribute route
GET /api/authors/1/articles            # nested index
```

Attributes only add routes **on top of** whatever nesting the parent declares.

<a name="inheritance"></a>
## Inheritance

Service-level attributes support class inheritance:

- `#[ApiScope]` **stacks** — parent and child scope paths/defaults/patterns are
  concatenated/merged (parent first).
- `#[ApiResource]` and `#[ApiActions]` on a child **override** the parent's.
- Abstract service classes are skipped (their routes are only connected through
  concrete subclasses).

```php
#[ApiScope('/base')]
abstract class BaseApiService extends FallbackService
{
}

#[ApiScope('/v1')]
#[ApiActions([FeaturedAction::class])]
class ArticlesService extends BaseApiService
{
    // /api/articles/base/v1/featured
}
```

<a name="caching"></a>
## Caching

Attribute discovery follows the CakePHP 6 model: the resolver scans your service
and action directories **once** and the **whole attribute collection is cached as a
single unit** under one cache key. Every subsequent `Service::initialize()` only
filters that cached collection — the scan/parse happens once per process.

**The cache is registered automatically.** When the plugin boots
(`config/bootstrap.php`) it registers, idempotently:

1. A `_cakedc_api_attributes_` **cache engine** (File, serialized, in
   `tmp/cache/persistent`), only if the host has not configured one.
2. A `default` **resolver config** pointing at that engine, scanning
   `src/Service/*.php` + `src/Service/**/*.php` with `validateFiles = true`, only
   if the host has not configured `default`.

So attribute routes work **out of the box** — no manual setup required.

### Overriding the cache

Both registrations respect an existing configuration (Cake-core style: defaults
provided, overridable). To use a different engine, duration, or scan scope,
configure it **before the plugin loads**:

```php
// config/bootstrap.php (before plugins load, or in Application::bootstrap())
use Cake\AttributeResolver\AttributeResolver;
use Cake\Cache\Cache;

Cache::setConfig('_cakedc_api_attributes_', [
    'className' => 'File',
    'path' => CACHE,
    'duration' => '+1 day',
]);

AttributeResolver::setConfig('default', [
    // include BOTH patterns: **/*.php does not match files directly in the folder
    'paths' => ['src/Service/*.php', 'src/Service/**/*.php'],
    'cache' => '_cakedc_api_attributes_',
    'validateFiles' => true,
]);
```

> [!TIP]
> `FileEngine` + `serialize => true` is the working baseline. The resolver README
> mentions PhpEngine as an optional optimization on top of a File adapter. In
> tests, use the `Array` engine (memory-only) or `'cache' => false`.

### Changing the scan scope at runtime

If you re-configure the resolver with a different `paths`/`basePath`/`excludePaths`
(not just on boot), the cached collection is stale. Invalidate it before
re-configuring, mirroring the Codex `CommandBus` pattern:

```php
if (AttributeResolver::getConfig('default') !== null) {
    AttributeResolver::clear('default');
    AttributeResolver::drop('default');
}
AttributeResolver::setConfig('default', [...new scope...]);
```

`bin/cake service routes <service>` prints attribute routes too — constructing
the service applies them.

<a name="attribute-reference"></a>
## Attribute Reference

All attributes live in the `CakeDC\Api\Service\Attribute` namespace.

| Attribute | Target | Repeatable | Description |
|---|---|---|---|
| `#[ApiActions]` | Service class | No | Lists the service's action classes. |
| `#[ApiRoute]` | Action class | Yes | Route with any HTTP method(s). |
| `#[ApiGet]` / `#[ApiPost]` / `#[ApiPut]` / `#[ApiPatch]` / `#[ApiDelete]` / `#[ApiOptions]` | Action class | Yes | HTTP method shortcut. |
| `#[ApiScope]` | Service class | Yes | Path prefix, defaults, patterns. |
| `#[ApiResource]` | Service class | No | REST resource route options. |

Dependencies: `crustum/cakephp-attribute-resolver` (`^1.0`) drives attribute
discovery and caching; the connector requires a resolver configuration (see
[Caching](#caching)).
