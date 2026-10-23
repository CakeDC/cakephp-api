# CakePHP API Plugin

- [Introduction](#introduction)
- [Quickstart](#quickstart)
    - [Installing the Plugin](#installing-the-plugin)
    - [Configuration](#configuration)
    - [Exposing Your First Resource](#exposing-your-first-resource)
    - [Next Steps](#quickstart-next-steps)
- [Request Lifecycle](#request-lifecycle)
- [Services](#services)
    - [Service Types](#service-types)
    - [Resolving Services](#resolving-services)
    - [Defining a Custom Service](#defining-a-custom-service)
    - [Actions Map](#actions-map)
    - [Service Extensions](#service-extensions)
    - [Attribute Routing](#attribute-routing)
    - [Renderers](#renderers)
        - [JSend (Default)](#jsend-default)
        - [Json](#json)
        - [Raw](#raw)
        - [Xml](#xml)
        - [File](#file)
        - [Flysystem](#flysystem)
    - [Responses](#responses)
        - [Error Codes](#error-codes)
- [Actions](#actions)
    - [CRUD Actions](#crud-actions)
    - [Authentication Actions](#authentication-actions)
    - [Custom Actions](#custom-actions)
    - [Action Extensions](#extensions)
        - [Default Extensions](#default-extensions)
        - [Paginate](#paginate)
        - [Sort](#sort)
        - [Filter](#filter)
        - [CursorPaginate](#cursor-paginate)
        - [ExtendedSort](#extended-sort)
        - [CrudHateoas](#crud-hateoas)
        - [CrudRelations](#crud-relations)
        - [Nested](#nested-extension)
        - [Cors](#cors-extension)
        - [CrudAutocompleteList](#crud-autocomplete-list)
        - [Authentication](#authentication-extension)
        - [Pagination, Sorting and Filtering](#pagination-sorting-and-filtering)
        - [HATEOAS & Relations](#hateoas-relations)
        - [CORS](#cors)
        - [Nested Resources](#nested-resources)
    - [Transformers](#transformers)
- [Authentication](#authentication)
    - [Authenticators](#authenticators)
    - [JWT Authentication](#jwt-authentication)
    - [Token Responses](#token-responses)
- [Two-Factor Authentication](#two-factor-authentication)
    - [One-Time Passwords](#one-time-passwords)
    - [Webauthn](#webauthn)
    - [RBAC Scopes](#rbac-scopes)
- [Authorization & Permissions](#authorization-permissions)
    - [Permission Format](#permission-format)
    - [Rules](#rules)
    - [Cached RBAC](#cached-rbac)
    - [CakeDC/Auth Integration](#cakedc-auth-integration)
- [Versioning](#versioning)
- [Model & JWT Refresh Tokens](#model-jwt-refresh-tokens)
- [Testing](#testing)
- [Console Commands](#console-commands)
- [Legacy & Obsolete Components](#legacy-obsolete-components)

<a name="introduction"></a>
## Introduction

The **CakePHP API** plugin exposes your CakePHP application as a REST API with a few lines of configuration. It sits on top of the CakePHP framework's HTTP layer and provides:

- **Service-driven endpoints** — a request to `/api/articles` is resolved to a *service*, which builds an *action* that reads and writes an ORM table.
- **Out-of-the-box CRUD** — `index`, `view`, `add`, `edit`, `delete` actions are provided by the fallback service with no service class required.
- **Authentication & authorization** — session, form, token and JWT authenticators, plus a config-driven RBAC layer.
- **Response rendering** — JSend by default, with JSON, XML and raw renderers available.
- **Optional versioning** — versioned service namespaces (`Service/v1`, `Service/v2`, …).
- **Reusable extensions** — pagination, sorting, filtering, HATEOAS links, relations, CORS and nested resources.

The plugin targets **CakePHP 5.x**.

<a name="quickstart"></a>
## Quickstart

<a name="installing-the-plugin"></a>
### Installing the Plugin

Install via Composer:

```bash
composer require cakedc/cakephp-api
```

Load the plugin and its configuration:

```bash
bin/cake plugin load CakeDC/Api
```

> [!NOTE]
> The plugin ships its default configuration in `config/api.php`. Load it in your application's `config/bootstrap.php` (or via the manifest system) so `Configure::read('Api.*')` is populated:

```php
Cake\Core\Configure::load('api');
```

Alternatively, load the plugin and configure it in `Application::bootstrap()`:

```php
// In src/Application.php
public function bootstrap(): void
{
    parent::bootstrap();

    $this->addPlugin('CakeDC/Api');
}
```

<a name="configuration"></a>
### Configuration

All plugin configuration lives under the `Api` configuration key. The important defaults are:

```php
'Api' => [
    // Fallback service used when no service class exists for an endpoint
    'ServiceFallback' => '\\CakeDC\\Api\\Service\\FallbackService',
    // Response renderer (JSend by default)
    'renderer' => 'CakeDC/Api.JSend',
    // Parser used to read request data
    'parser' => 'CakeDC/Api.Form',

    // Route inflection: 'underscore', 'dasherize' or false
    'routesInflectorMethod' => false,

    // Versioning is disabled by default
    'useVersioning' => false,
    'versionPrefix' => 'v',

    // Service class lookup
    'lookupMode' => 'underscore',

    // JWT tokens (disabled by default)
    'Jwt' => [
        'enabled' => false,
        'AccessToken' => ['lifetime' => 600, 'secret' => ''],
        'RefreshToken' => ['lifetime' => 14 * 86400, 'secret' => ''],
    ],
    // 2FA (disabled by default)
    '2fa' => ['enabled' => false],
],
```

> [!TIP]
> JWT and 2FA are **off** by default. Enable them only when your application needs them.

<a name="exposing-your-first-resource"></a>
### Exposing Your First Resource

No service class is required for a basic CRUD resource. If your application has a `ArticlesTable` (ORM table), the plugin will serve it automatically:

```
GET    /api/articles          -> articles index
GET    /api/articles/1        -> view article 1
POST   /api/articles          -> add an article
PUT    /api/articles/1        -> edit article 1
DELETE /api/articles/1        -> delete article 1
```

A `GET /api/articles` response (JSend):

```json
{
    "status": "success",
    "data": [
        {"id": 1, "title": "Hello", "published": "Y"}
    ],
    "pagination": {
        "count": 1,
        "page": 1,
        "pages": 1,
        "limit": 20
    }
}
```

By default the plugin is configured for **public access** (`Auth.allow = '*'`). Read [Authorization & Permissions](#authorization-permissions) to restrict access.

> [!WARNING]
> **The fallback service exposes any table by its URL — this is unsafe without an auth layer.**
>
> Any request to `/api/{name}` that has no service class resolves to a `FallbackService` over the table `{Name}` (pluralized). Because the default configuration is public (`Auth.allow = '*'`), an unauthenticated request to `/api/users` would return the **entire users table** — including password hashes, `api_token` values, and anything else the entity serializes.
>
> Before going to production you **must**:
>
> 1. Enable authentication and RBAC permissions (see [Authorization & Permissions](#authorization-permissions)) so only authenticated/authorized requests are allowed.
> 2. Explicitly deny (or create a service class for) sensitive resources such as `users`.
>
> Never rely on the fallback service for resources that contain sensitive data.

<a name="quickstart-next-steps"></a>
#### Next Steps

Once your first endpoint works, read about [Services](#services), [Authentication](#authentication), and [Extensions](#extensions) to tailor the plugin to your application.

<a name="request-lifecycle"></a>
## Request Lifecycle

A request to `/api/articles/1` is processed by the middleware chain registered in `Api.Middleware`:

1. **`BodyParserMiddleware`** — parses the request body into `$request->getData()`.
2. **`AuthenticationMiddleware`** — resolves the identity (session, form, token or JWT) and stores it in the `authentication` request attribute.
3. **`ParseApiRequestMiddleware`** — matches the URL against `#/api/{service}{base}#` (or the versioned pattern), resolves the service, and attaches it to the request as the `service` attribute. If no service resolves, it responds with the error directly.
4. **`AuthorizationMiddleware`** (RBAC) — checks the request against the [permissions](#authorization-permissions).
5. **`ProcessApiRequestMiddleware`** — executes the service action and renders the result through the configured [renderer](#renderers).

The URL structure is:

```
/api/{service}[/{action|nested-id}[/...]]
```

Service and action names are **underscored** by default. For example `/api/auth/jwt_login` resolves to the `AuthService` and its `jwt_login` action.

<a name="overloaded-router"></a>
### Overloaded Router (`ApiRouter`)

The plugin **overloads** the CakePHP `Router` with `CakeDC\Api\Routing\ApiRouter`. It is what turns a service's actions map into real resource routes at runtime:

- **`resources($serviceName, $options)`** — generates the collection + item routes (`/articles`, `/articles/{id}`) from the service's actions map and HTTP methods.
- **`parseRequest($request)`** — resolves a URL into routing params (controller/action/pass/`_method`); it is what `Service::parseRoute()` delegates to.
- **Reverse routing** — `ApiRouter::reverse($params)` / `ApiRouter::url($route)` rebuild a URL from route params; services expose this via `routeReverse()` and `routeUrl()`. The HATEOAS links produced by `CrudHateoas` and `ReverseRouting` rely on it.
- **Named expressions** — `ID`, `UUID`, `YEAR`, `MONTH`, `DAY`, `ACTION` constants for route templates (e.g. `'/articles/{id}'`).

Routes are registered **per service** inside a `routesWrapper()` that reloads the router, connects the service's resource routes, runs the callback, and resets the router — so a service never pollutes the global route table. Inspect what a service exposes with `bin/cake service routes <service>` (see [Console Commands](#console-commands)).

<a name="services"></a>
## Services

A **service** is the unit that owns an endpoint. It is responsible for:

- Knowing its **name** (`articles`, `auth`, …) and base URL.
- Declaring an **actions map** (which HTTP method + path maps to which action).
- Building **actions** for an incoming request.
- Holding the **request** and **response** objects for the duration of the call.

<a name="service-types"></a>
### Service Types

| Service | Purpose |
| --- | --- |
| `FallbackService` | Default CRUD service over an ORM table (used when no class is found). |
| `CrudService` | Abstract base for services that expose CRUD actions. |
| `NestedCrudService` | Adds nested-resource support (e.g. `/authors/1/articles`). |
| `AuthService` | Authentication actions: `login`, `register`, `jwt_login`, `otp_verify`, … |
| `DescribeService` / `ListingService` | Introspection endpoints (`/api/describe`, `/api/list`). |
| `RecaptchaService` | `validate` action using the reCaptcha trait. |

<a name="resolving-services"></a>
### Resolving Services

Services are resolved and cached by `ServiceRegistry` / `ServiceLocator`:

```php
use CakeDC\Api\Service\ServiceRegistry;

$service = ServiceRegistry::getServiceLocator()->get('articles', [
    'version' => null,
    'request' => $request,
    'response' => $response,
    'baseUrl' => '/articles',
]);
```

Resolution order (`lookupMode = underscore`):

1. A class named `{App}\Service\ArticlesService` (camelized alias + `Service` suffix).
2. The same class in each plugin listed in `Api.serviceLookupPlugins` (defaults to `CakeDC/Api`).
3. The **fallback service** (`Api.ServiceFallback`, i.e. `FallbackService`) — a generic CRUD service over the `Articles` table.

> [!WARNING]
> **The fallback is a catch-all.** Any endpoint with no service class silently falls back to CRUD over the matching table (`/api/users` → the `Users` table, `/api/orders` → the `Orders` table, …). Combined with the default public `Auth.allow = '*'` configuration this lets unauthenticated requests read every serialized field of those tables (e.g. `GET /api/users`).
>
> Treat the fallback as **development convenience only**. For production:
>
> - secure the API with authentication + RBAC (see [Authorization & Permissions](#authorization-permissions)), and
> - add explicit [permissions](#permission-format) that deny services you do not want exposed, or define dedicated service classes for them.

> [!WARNING]
> Service names are single words under the default `underscore` lookup mode. Multi-word names currently do not resolve consistently (`lookupMode = dasherize` is the workaround).

<a name="defining-a-custom-service"></a>
### Defining a Custom Service

Create a service class in your application's `Service` directory:

```php
<?php
declare(strict_types=1);

namespace App\Service;

use CakeDC\Api\Service\FallbackService;

class ArticlesService extends FallbackService
{
    protected array $actions = [
        'featured' => ['method' => ['GET'], 'path' => 'featured'],
        'data' => ['method' => ['GET'], 'path' => 'data'],
    ];

    public function initialize(): void
    {
        parent::initialize();

        $this->setTable('Articles');
    }
}
```

Now `/api/articles/featured` and `/api/articles/data` are available in addition to the CRUD routes.

> [!NOTE]
> `FallbackService::initialize()` infers the table from the service name (`articles` → `ArticlesTable`). Override with `setTable()` when the table name differs.

<a name="actions-map"></a>
### Actions Map

The actions map declares which HTTP method + path an action responds to:

```php
protected array $actions = [
    'featured' => ['method' => ['GET'], 'path' => 'featured'],
    'publish' => ['method' => ['POST'], 'path' => '{id}/publish'],
];
```

Within `initialize()`, you can register an action with a specific action class and extra options:

```php
public function initialize(): void
{
    parent::initialize();

    $this->mapAction('data', DataAction::class, [
        'method' => ['GET'],
        'path' => 'data',
        'mapCors' => true, // also register an OPTIONS route for CORS preflight
    ]);
}
```

<a name="attribute-routing"></a>
### Attribute Routing

Routes can also be declared directly on the service and action classes with PHP
attributes (`#[ApiActions]`, `#[ApiRoute]`, `#[ApiGet]`, `#[ApiScope]`,
`#[ApiResource]`), following the CakePHP 6 attribute-routing approach and backed
by the `crustum/cakephp-attribute-resolver` package. See
[Attribute Routing for Services](attributes.md) for the full reference.

<a name="service-extensions"></a>
### Service Extensions

Service-level extensions attach to `Service.beforeDispatch` / `Service.afterDispatch` and modify the whole service lifecycle rather than a single action (action-level extensions are covered under [Extensions](#extensions)).

#### Collection

Adds **bulk collection** routes to the service on `Service.beforeDispatch`:

| Route | HTTP | Action |
| --- | --- | --- |
| `/api/{service}/bulk` | POST | `AddEditAction` (bulk create) |
| `/api/{service}/bulk` | PUT | `AddEditAction` (bulk update) |
| `/api/{service}/bulk` | DELETE | `DeleteAction` (bulk delete) |

Each route also registers an OPTIONS (CORS) counterpart.

#### Log

Logs request timing for the service. Starts a timer on `Service.beforeDispatch` and logs the elapsed time (and outcome) on `Service.afterDispatch`. Useful for profiling API endpoints in development.

#### OptionsHandler

Answers **OPTIONS** requests (and forced CORS preflights) with a `DummyAction`, returning an empty result so preflight requests get a response without hitting a real action. Useful when clients send `Access-Control-Request-Method` preflights.

<a name="renderers"></a>
### Renderers

The renderer determines the response body format and is set via `Api.renderer`:

```php
'Api' => [
    'renderer' => 'CakeDC/Api.JSend',
],
```

All renderers extend `CakeDC\Api\Service\Renderer\BaseRenderer` and implement three methods:

- `accept()` — content negotiation: whether the renderer can serve the current request (`Accept` header).
- `response(?Result $result)` — build the success body from a `Result` (data + payload).
- `error(Exception $exception)` — build the error body when a service call throws.

BaseRenderer also provides the shared `buildMessage()`, `stackTrace()`, and JSON `encode()` helpers used by the JSON-family renderers.

<a name="jsend-default"></a>
#### JSend (Default)

`CakeDC/Api.JSend` wraps every response in the [JSend](https://github.com/omniti-labs/jsend) envelope and is the default renderer.

- Accepts `application/json`, `text/json`, or `text/javascript`.
- `success` status when the result code is `0` or in `200-399`; `error` otherwise.
- On errors the **HTTP status is forced to `200`** (the `errorCode` property) — the real code lives in the body:

```json
{"status": "error", "message": "Missing route", "code": 404, "data": null}
```

- In debug mode the body is pretty-printed and an exception `trace` is included.
- `ValidationException` puts the field errors under `data`.

> [!WARNING]
> Because JSend keeps the HTTP status at `200` even for failures, clients must inspect the body `status`/`code`, not the HTTP status code.

<a name="json"></a>
#### Json

`CakeDC/Api.Json` returns plain JSON with **no envelope** — the result data is the body (payload merged into the data).

```json
[{"id": 1, "title": "Hello", "published": "Y"}]
```

Errors are returned as `{"error": {"code": ..., "message": ..., "trace": ..., "validation": {...}}}` (trace and validation only when applicable). Unlike JSend, the HTTP status is set from the result code.

<a name="raw"></a>
#### Raw

`CakeDC/Api.Raw` returns the raw payload without JSON encoding:

- Arrays are rendered with `print_r()`.
- Scalars/strings are cast to the body directly.

Content type is `text/plain`; errors are the plain exception message (with file/line and a `print_r` trace in debug mode). Useful for debugging or non-JSON consumers.

<a name="xml"></a>
#### Xml

`CakeDC/Api.Xml` serializes the result data to XML with content type `application/xml`. Errors are rendered as an `<error>` structure containing `code` and `message` (plus `trace` in debug mode and `validation` for `ValidationException`).

<a name="file"></a>
#### File

`CakeDC/Api.File` streams a file as the response body using `Response::withFile()`:

```php
// The action returns a filesystem path as its data
return '/srv/assets/report.pdf';
```

The HTTP status is set from the result code. Errors fall back to a JSON error body.

<a name="flysystem"></a>
#### Flysystem

`CakeDC/Api.Flysystem` extends the file renderer to stream a file from a [Flysystem](https://flysystem.thephpleague.com) filesystem. The result data must contain:

```php
return [
    'filesystem' => $filesystem, // League\Flysystem\Filesystem
    'path' => 'reports/report.pdf',
    'name' => 'report.pdf',      // download name
];
```

A missing file yields an HTTP `404`.

<a name="responses"></a>
### Responses

Every service/action call produces a `Result` that carries:

- **data** — the action payload.
- **code** — the application-level result code (`200`, `404`, `422`, `500`, …).
- **exception** — the exception that produced the error, when applicable.
- **payload** — extra response metadata (e.g. pagination, links).

The result is rendered by the configured renderer.

<a name="error-codes"></a>
#### Error Codes

| Code | Meaning |
| --- | --- |
| `200` | Success. |
| `400` | Bad request / generic service error. |
| `401` | Not authenticated. |
| `403` | Not authorized (RBAC denied). |
| `404` | Route or record not found. |
| `405` | Method not allowed. |
| `422` | Validation failed (field errors under `data`). |
| `500` | Server error. |

Validation failures return the field errors under the response `data` key:

```json
{
    "status": "error",
    "message": "Validation failed",
    "code": 422,
    "data": {
        "title": ["This field is required"]
    }
}
```

<a name="actions"></a>
## Actions

An **action** is a single endpoint implementation (`index`, `view`, `login`, …). Actions read data from the request, execute, and return data that is wrapped into a `Result`.

<a name="crud-actions"></a>
### CRUD Actions

The `FallbackService`/`CrudService` provide these actions out of the box:

| Action | Route | HTTP |
| --- | --- | --- |
| `index` | `/api/articles` | GET |
| `view` | `/api/articles/{id}` | GET |
| `add` | `/api/articles` | POST |
| `edit` | `/api/articles/{id}` | PUT |
| `delete` | `/api/articles/{id}` | DELETE |
| `describe` | `/api/articles` | OPTIONS |

<a name="authentication-actions"></a>
### Authentication Actions

The `AuthService` exposes these endpoints under `/api/auth`:

| Action | HTTP | Description |
| --- | --- | --- |
| `login` | POST | Form login, returns the session identity. |
| `register` | POST | Register a new user. |
| `reset_password_request` | POST | Request a password reset. |
| `reset_password` | POST | Reset the password with a token. |
| `validate_account_request` | POST | Request account validation. |
| `validate_account` | POST | Validate the account with a token. |
| `social_login` | POST | Login through a social provider. |
| `jwt_login` | POST | Login and issue JWT access + refresh tokens. |
| `jwt_refresh` | POST | Refresh an access token. |
| `jwt_social_login` | POST | Social login issuing JWT tokens. |
| `otp_verify` | GET | Fetch/verify the OTP shared secret (QR code). |
| `otp_verify_check` | POST | Verify an OTP code. |
| `webauthn2fa` | GET | 2FA status. |
| `webauthn2fa_register` / `webauthn2fa_register_options` | GET/POST | Webauthn registration. |
| `webauthn2fa_auth` / `webauthn2fa_auth_options` | GET/POST | Webauthn authentication. |

> [!NOTE]
> The users table is provided by the **CakeDC/Users** plugin. `ApiInitializer` resolves identities against `CakeDC/Users.Users` with the `active` finder.

<a name="custom-actions"></a>
### Custom Actions

Create an action by extending `CakeDC\Api\Service\Action\Action` (or `CrudAction` for table-backed actions) and registering it in the service:

```php
<?php
declare(strict_types=1);

namespace App\Service\Action;

use CakeDC\Api\Service\Action\CrudAction;

class FeaturedAction extends CrudAction
{
    public function execute(): mixed
    {
        return $this->getTable()
            ->find()
            ->where(['published' => 'Y'])
            ->toArray();
    }
}
```

Register it in the service:

```php
$this->mapAction('featured', FeaturedAction::class, [
    'method' => ['GET'],
    'path' => 'featured',
]);
```

<a name="extensions"></a>
### Action Extensions

**Action extensions** (`CakeDC\Api\Service\Action\Extension\*`) are the sub-action layer: attached per action via the `Extension` config key, they hook into `Action.Crud.*` / `Action.Auth.*` events. Service-level extensions are covered under [Services → Service Extensions](#service-extensions).

Action extensions are enabled in configuration:

```php
'Api' => [
    'Service' => [
        'default' => [
            'Action' => [
                'default' => [
                    'Extension' => [
                        'CakeDC/Api.Cors',
                        'CakeDC/Api.Sort',
                        'CakeDC/Api.CrudHateoas',
                        'CakeDC/Api.CrudRelations',
                    ],
                ],
                'Index' => [
                    'Extension' => ['CakeDC/Api.Paginate'],
                ],
            ],
        ],
    ],
],
```

<a name="default-extensions"></a>
#### Default Extensions

The default set is defined under `Api.Service.default.Action.default.Extension` (shown above), plus per-action overrides such as `Index`.

<a name="paginate"></a>
#### Paginate

Applies pagination to index queries and appends a `pagination` payload to the response.

- Query params: `page` and `limit` (field names configurable via `pageField` / `limitField`).
- Default page size: `20` (`defaultLimit`).
- Listens to `Action.Crud.onFindEntities` (limit + page) and `Action.Crud.afterFindEntities` (pagination metadata).

```json
{
    "status": "success",
    "data": [...],
    "pagination": {"count": 42, "page": 2, "pages": 5, "limit": 10}
}
```

> [!NOTE]
> `pagination.count` is the **total** row count, not the page size.

<a name="sort"></a>
#### Sort

Orders index queries by a single field.

- Query params: `sort` and `direction` (field names via `sortField` / `directionField`).
- Default direction: `asc`.
- Listens to `Action.Crud.onFindEntities` and applies `orderBy`.

```
GET /api/posts?sort=title&direction=desc
```

<a name="filter"></a>
#### Filter

Filters index queries on any schema column of the table.

- Field values in the request data are turned into `WHERE` conditions for matching schema columns.
- Supports comparison postfixes on the field name:

| Postfix | Operator |
| --- | --- |
| *(none)* | `=` |
| `ge` | `>=` |
| `le` | `<=` |
| `gt` | `>` |
| `lt` | `<` |
| `like` / `llike` / `rlike` | `LIKE` |
| `ne` | `!=` |

```
GET /api/posts?published=Y&views$ge=100
```

<a name="cursor-paginate"></a>
#### CursorPaginate

Cursor-based pagination, better suited to large/fast-changing datasets than page-based pagination.

- Config: `cursorField` (default `id`), `countField` (`count`), `defaultCount` (`20`), `maxIdField` (`max_id`), `sinceIdField` (`since_id`).
- Accepts `count`, `max_id`, `since_id` query params and emits prev/next links via `ReverseRouting`.

<a name="extended-sort"></a>
#### ExtendedSort

Multi-field sorting. The `sort` param is a JSON-encoded associative array passed straight to `orderBy`:

```
GET /api/posts?sort={"title":"asc","created":"desc"}
```

<a name="crud-hateoas"></a>
#### CrudHateoas

Adds a `links` collection to `index`/`view` responses describing the available actions (self, add, edit, delete, and parent links for nested resources).

```json
"links": [
    {"name": "self", "href": "http://example.com/api/articles/1", "rel": "/api/articles/1", "method": "GET"},
    {"name": "articles:edit", "href": "http://example.com/api/articles/1", "rel": "/api/articles/1", "method": "PUT"}
]
```

<a name="crud-relations"></a>
#### CrudRelations

Eager-loads **HasOne** and **BelongsTo** associations so related entities are included in `index`, `view`, and `edit` responses. Listens to `Action.Crud.onFindEntities` / `Action.Crud.onFindEntity`.

<a name="nested-extension"></a>
#### Nested

Scopes queries by the parent resource for nested endpoints (e.g. `/api/authors/1/articles`). Listens to `onFindEntities`, `onFindEntity`, and `onPatchEntity`, filtering by the parent foreign key (`getParentId()` / `getParentIdName()`). Added automatically by `NestedCrudService`. See also [Nested Resources](#nested-resources).

<a name="cors-extension"></a>
#### Cors

Appends CORS headers to the response (see [CORS](#cors)). Listens to `Action.beforeProcess`.

<a name="crud-autocomplete-list"></a>
#### CrudAutocompleteList

Transforms an index query into an autocomplete list. When the request carries an `autocomplete_list` param, the query selects only the id + display fields.

```
GET /api/articles?autocomplete_list=title
```

<a name="authentication-extension"></a>
#### Authentication

Provides per-action authentication for the action layer:

- Config: `requireIdentity` (default `true`), `identityAttribute` (`identity`), `logoutRedirect`.
- Actions registered via `allowUnauthenticated()` skip identity checks.
- Listens to `Action.Auth.onAuthentication` / `Action.onAuth`; throws `UnauthenticatedException` when an identity is required but absent.

<a name="pagination-sorting-and-filtering"></a>
#### Pagination, Sorting and Filtering

```
GET /api/posts?page=2&limit=10&sort=title&direction=asc&published=Y
```

```json
{
    "status": "success",
    "data": [...],
    "pagination": {
        "count": 42,
        "page": 2,
        "pages": 5,
        "limit": 10
    }
}
```

> [!NOTE]
> `pagination.count` is the **total** row count, not the page size.

<a name="hateoas-relations"></a>
#### HATEOAS & Relations

With `CakeDC/Api.CrudHateoas`, item and index responses include a `links` collection:

```json
"links": [
    {"name": "self", "href": "http://example.com/api/articles/1", "rel": "/api/articles/1", "method": "GET"},
    {"name": "articles:edit", "href": "http://example.com/api/articles/1", "rel": "/api/articles/1", "method": "PUT"},
    {"name": "articles:delete", "href": "http://example.com/api/articles/1", "rel": "/api/articles/1", "method": "DELETE"},
    {"name": "articles:index", "href": "http://example.com/api/articles", "rel": "/api/articles", "method": "GET"}
]
```

<a name="cors"></a>
#### CORS

The `Cors` extension appends CORS headers to the response for requests with an `Origin` header:

```php
$this->_request['headers']['Origin'] = 'http://foobar.com';
```

```
Access-Control-Allow-Origin: *
Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS, HEAD, PATCH
Access-Control-Allow-Credentials: true
Access-Control-Max-Age: 300
```

<a name="nested-resources"></a>
#### Nested Resources

The plugin supports **nested resources** out of the box. `FallbackService::loadRoutes()` walks the table's `HasMany` associations and registers nested routes automatically:

```
GET    /api/authors/1/articles          -> articles index scoped to author 1
GET    /api/authors/1/articles/1        -> article 1 of author 1
POST   /api/authors/1/articles          -> add an article under author 1
PUT    /api/authors/1/articles/1        -> edit article 1 of author 1
DELETE /api/authors/1/articles/1        -> delete article 1 of author 1
```

How nesting works:

- **`NestedCrudService`** is the base for nested-aware services; it injects the `CakeDC/Api.Nested` extension into the child action options.
- **`NestedExtension`** scopes every query by the parent foreign key — on `onFindEntities` / `onFindEntity` it adds `where([$parentIdName => $parentId])`, and on `onPatchEntity` it enforces the parent when patching.
- **Parent resolution** — when the route's controller matches one of the parent's `innerServices`, the plugin resolves the child service through `ServiceRegistry`, calls `setParentService($parent)` on it, and the child exposes `getParentService()`. `innerServices` is populated automatically from the parent table's `HasMany` associations.
- **HATEOAS** — nested index/view responses include a parent link (`authors:view`) pointing back to the parent resource.
- **Arbitrary nesting depth** — because each level resolves a child service with its own parent, `/api/authors/1/articles/2/tags` works the same way.

Use `getParentService()` inside actions when you need access to the parent record or its id.

<a name="transformers"></a>
### Transformers

Transformers shape **action response data** — they convert entities/arrays (the model layer) into the API response format an action returns. Implement `CakeDC\Api\Transformer\TransformerInterface` (`transform(mixed $data): array`) or extend `AbstractTransformer` for the built-in helpers:

- `when($condition, $value, $default)` — conditional field value.
- `get($data, $key, $default)` — value from array, object, or entity.
- `timestamp($date)` — ISO-8601 formatting of dates.
- `item($entity, $class)` / `collection($entities, $class)` — transform nested items.
- `matchingData(...)` / `joinData(...)` — transform CakePHP association payloads.

```php
<?php
declare(strict_types=1);

namespace App\Transformer;

use CakeDC\Api\Transformer\AbstractTransformer;

class ArticleTransformer extends AbstractTransformer
{
    public function transform(mixed $data): array
    {
        return [
            'id' => $this->get($data, 'id'),
            'title' => $this->get($data, 'title'),
            'published_at' => $this->timestamp($this->get($data, 'published')),
        ];
    }
}
```

A transformer is used inside an action to shape the payload before it is wrapped into a `Result` and rendered. `all()` returns a collection (`CollectionInterface`), so the transform is applied lazily via the collection's `map()`:

```php
<?php
declare(strict_types=1);

namespace App\Service\Action;

use App\Transformer\ArticleTransformer;
use CakeDC\Api\Service\Action\CrudAction;

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
```

The mapped collection becomes the action's result data and is rendered by the configured renderer.

<a name="authentication"></a>
## Authentication

`ApiInitializer` builds the `AuthenticationService` used by the `AuthenticationMiddleware`.

<a name="authenticators"></a>
### Authenticators

The following authenticators are loaded:

- **`Authentication.Session`** — reads the identity from the session (`Auth` key).
- **`CakeDC/Auth.Form`** — form login against the users table.
- **`Authentication.Token`** — static token via `?token=` query param, matched against the `api_token` column.
- **`Authentication.Jwt`** — JWT bearer tokens (`Authorization: Bearer <token>`), HS512, signed with `Api.Jwt.AccessToken.secret`.

<a name="jwt-authentication"></a>
### JWT Authentication

Enable JWT in your configuration:

```php
'Api' => [
    'Jwt' => [
        'enabled' => true,
        'AccessToken' => [
            'lifetime' => 600,
            'secret' => env('JWT_SECRET'), // >= 512 bits for HS512
        ],
        'RefreshToken' => [
            'lifetime' => 14 * 86400,
            'secret' => env('JWT_REFRESH_SECRET'),
        ],
    ],
],
```

> [!IMPORTANT]
> HS512 requires a signing key of **at least 512 bits** (64 bytes). Shorter secrets cause `Lcobucci\JWT\Signer\InvalidKeyProvided`.

Login issues both tokens:

```
POST /api/auth/jwt_login
{
    "username": "user-1",
    "password": "12345"
}
```

```json
{
    "status": "success",
    "data": {
        "id": 1,
        "username": "user-1",
        "access_token": "eyJ0eXAi...",
        "refresh_token": "eyJ0eXAi...",
        "expired": "2026-08-15T12:00:00+00:00",
        "enabled2FA": false,
        "enabledWebauthn": false,
        "enabledOtp": false
    }
}
```

> [!NOTE]
> The three `enabled*` flags are all `false` unless `Api.2fa.enabled` is turned on (and the relevant checker requires the user).

Refresh tokens are persisted in the `jwt_refresh_tokens` table (upserted per `model` + `foreign_key`).

<a name="token-responses"></a>
### Token Responses

The token payload strips sensitive fields (`secret`, `secret_verified`, `additional_data`) and includes the 2FA flags `enabled2FA`, `enabledWebauthn` and `enabledOtp`.

The JWT `aud` claim depends on 2FA state:

- Normal login → `Router::url('/', true)`.
- 2FA-enabled login (`type = login` with 2FA active) → `Router::url('/2fa', true)` — this is what the RBAC `TwoFactorScope` checks.

<a name="two-factor-authentication"></a>
## Two-Factor Authentication

2FA is disabled by default:

```php
'Api' => [
    '2fa' => ['enabled' => false],
    'OneTimePasswordAuthenticator' => [
        'login' => false,
        'checker' => \CakeDC\Api\Service\Auth\TwoFactorAuthentication\DefaultOneTimePasswordAuthenticationChecker::class,
    ],
    'Webauthn2fa' => [
        'checker' => \CakeDC\Api\Service\Auth\TwoFactorAuthentication\DefaultWebauthn2fAuthenticationChecker::class,
    ],
],
```

<a name="one-time-passwords"></a>
### One-Time Passwords

OTP uses `RobThree\Auth\TwoFactorAuth` (TOTP, base32 shared secret).

- `GET /api/auth/otp_verify` — returns the shared secret and a QR-code data URI (`secretDataUri`) until the secret is marked verified.
- `POST /api/auth/otp_verify_check` — verifies a code against the secret and marks `secret_verified`.

The checker contract:

```php
$checker->isEnabled();                       // Configure::read('Api.OneTimePasswordAuthenticator.login') !== false
$checker->isRequired($user);                 // non-empty user && enabled
```

<a name="webauthn"></a>
### Webauthn

Webauthn registration and authentication are exposed through the `webauthn2fa_*` actions. The checker reads `Api.Webauthn2fa.enabled`; custom checkers can be injected via the `checker` config keys.

<a name="rbac-scopes"></a>
### RBAC Scopes

Two RBAC rules gate 2FA flows (used inside permissions as `rule` entries):

| Rule | Allows when |
| --- | --- |
| `CakeDC\Api\Rbac\Rules\TwoFactorScope` | the JWT `aud` claim equals `Router::url('/2fa', true)` |
| `CakeDC\Api\Rbac\Rules\TwoFactorPassedScope` | the JWT `aud` claim equals `Router::url('/', true)` |

A typical permission grants the 2FA endpoints only to tokens whose audience is `/2fa`, and everything else to fully-verified tokens:

```php
return [
    'CakeDC/Auth.api_permissions' => [
        ['role' => '*', 'service' => 'Auth', 'action' => ['OtpVerify', 'OtpVerifyCheck'], 'rule' => [
            'className' => \CakeDC\Api\Rbac\Rules\TwoFactorScope::class,
        ]],
        ['role' => '*', 'service' => '*', 'action' => '*', 'rule' => [
            'className' => \CakeDC\Api\Rbac\Rules\TwoFactorPassedScope::class,
        ]],
    ],
];
```

<a name="authorization-permissions"></a>
## Authorization & Permissions

Authorization is handled by the **CakeDC/Auth** `RbacPolicy`, backed by the plugin's `ApiRbac` adapter. Each permission is a rule array; the first matching rule decides the outcome.

Permissions are loaded from a configuration file (`ApiConfigProvider`), keyed as `CakeDC/Auth.api_permissions`:

```php
// config/api_permissions.php
return [
    'CakeDC/Auth.api_permissions' => [
        // Admin can do everything
        ['role' => 'admin', 'service' => '*', 'action' => '*'],
        // Public login is always allowed
        ['role' => '*', 'service' => 'Auth', 'action' => 'login', 'bypassAuth' => true],
    ],
];
```

> [!TIP]
> If the config file is missing, the plugin falls back to `ApiConfigProvider`'s default permissions (Auth bypass + admin-all + user-GET).

<a name="permission-format"></a>
### Permission Format

A permission entry is a plain array:

| Key | Description |
| --- | --- |
| `role` | User role(s); missing → `*`. |
| `service` | Service name(s); `*` matches any. |
| `action` | Action name(s); `*` matches any. |
| `method` | HTTP method (`GET`, `POST`, …). |
| `bypassAuth` | `true` → allow without authentication. |
| `allowed` | Explicit `true`/`false`; defaults to `true`. |
| `rule` | Callable, `Rule` instance, or `['className' => ..., 'options' => [...]]`. |
| any other key | Matched against the user array (e.g. `'id' => 1`). |

- Action names in config use **camelized** form (`MyAction`), matching the route's underscored `my_action`.
- Keys prefixed with `*` are **inverted** (`'*service' => 'articles'` denies when the service is `articles`).
- A permission missing both `service` and `action` is rejected (`cannot evaluate`).

<a name="rules"></a>
### Rules

```php
// Callable
['role' => 'user', 'service' => 'articles', 'action' => 'index',
    'rule' => fn($user, $role, $request) => $user['id'] === 1],

// Rule object
['role' => 'user', 'service' => 'articles', 'action' => 'edit',
    'rule' => new IsOwnerRule()],

// Rule class via the rule registry
['role' => 'user', 'service' => 'articles', 'action' => 'edit',
    'rule' => ['className' => \App\Rbac\IsOwnerRule::class]],
```

<a name="cached-rbac"></a>
### Cached RBAC

For high-throughput applications, `CachedApiRbac` precomputes a permissions map (keyed by role → service) and stores it in the cache engine `_cakedc_api_auth_`:

```php
use CakeDC\Api\Rbac\CachedApiRbac;

// Use CachedApiRbac as the RBAC adapter instead of ApiRbac
```

> [!NOTE]
> The cache engine name is currently hardcoded to `_cakedc_api_auth_`; configure that engine (or an Array engine in tests) before using `CachedApiRbac`.

<a name="cakedc-auth-integration"></a>
### CakeDC/Auth Integration

The plugin is a first-class citizen of the **CakeDC/Auth** authorization stack. `ApiInitializer::getAuthorizationService()` wires the RBAC adapter into CakePHP's `Authorization` plugin:

```php
$map = new MapResolver();
$rbac = new ApiRbac(); // swap for CachedApiRbac to enable the cached permissions map
$map->map(
    ServerRequest::class,
    new CollectionPolicy([
        new RbacPolicy(['adapter' => $rbac]),
    ])
);
$resolver = new ResolverCollection([$map, new OrmResolver()]);

return new AuthorizationService($resolver);
```

What that gives you:

- **`RbacPolicy`** evaluates every API request against the permissions (see [Authorization & Permissions](#authorization-permissions)); the `adapter` is the plugin's `ApiRbac`.
- **Cached case** — swap the adapter for `CachedApiRbac`: it precomputes a permissions map (role → service → rules) once via `buildPermissionsMap()`, stores it under the `_cakedc_api_auth_` cache engine, and `checkPermissions()` walks the cached map instead of re-evaluating every rule — useful for high-traffic APIs.
- **2FA-aware rules** — the same `RbacPolicy` accepts the plugin's `TwoFactorScope` / `TwoFactorPassedScope` rules for gating 2FA endpoints (see [RBAC Scopes](#rbac-scopes)).
- Permissions load from `CakeDC/Auth.api_permissions` via `ApiConfigProvider`, with a sensible default when the config file is missing.

<a name="versioning"></a>
## Versioning

Enable versioning in the configuration:

```php
'Api' => [
    'useVersioning' => true,
    'versionPrefix' => 'v',
],
```

With versioning enabled, the URL prefix includes the version and the service is resolved from a versioned namespace:

```
/api/v1/articles    -> App\Service\v1\ArticlesService
/api/v2/articles    -> App\Service\v2\ArticlesService
```

```php
// src/Service/v1/ArticlesService.php
namespace App\Service\v1;

use App\Service\ArticlesService as BaseArticlesService;

class ArticlesService extends BaseArticlesService
{
}
```

> [!NOTE]
> The version directory mirrors the version string (`v1` → `Service/v1`). An unresolvable version falls back to `FallbackService`. `Api.defaultVersion` provides the version used when none is given.

`/api/describe` and `/api/list` also honor the version prefix when versioning is enabled.

<a name="model-jwt-refresh-tokens"></a>
## Model & JWT Refresh Tokens

The plugin ships two ORM tables:

| Table | Purpose |
| --- | --- |
| `jwt_refresh_tokens` | Persists refresh tokens (`model`, `foreign_key`, `token`, `expired`); the `token` is hidden from JSON serialization. |
| `auth_store` | Stores serialized authentication state (used by the Webauthn adapters); the `store` column is typed as JSON. |

> [!NOTE]
> `AuthStore`'s validator uses `scalar('store')`, so array payloads are rejected by the standard marshalling path — pass a scalar (e.g. a JSON string) or save with `['validate' => false]`.

<a name="testing"></a>
## Testing

The plugin ships an `IntegrationTestCase` that boots the real HTTP stack. The test application under `tests/App` provides sample services (`ArticlesService`, `PostsService`, `TagsService`) and fixtures.

```php
<?php
declare(strict_types=1);

namespace App\Test\TestCase;

use CakeDC\Api\Test\ConfigTrait;
use CakeDC\Api\Test\Settings;
use CakeDC\Api\TestSuite\IntegrationTestCase;

class ArticlesApiTest extends IntegrationTestCase
{
    use ConfigTrait;

    protected array $fixtures = [
        'plugin.CakeDC/Api.Articles',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->_tokenAccess();
        $this->getDefaultUser(Settings::USER1);
    }

    public function testIndex(): void
    {
        $this->sendRequest('/articles', 'GET', ['limit' => 5]);
        $result = $this->getJsonResponse();
        $this->assertSuccess($result);
        $this->assertNotEmpty($result['data']);
    }

    public function testValidationError(): void
    {
        $this->sendRequest('/articles', 'POST', ['title' => '']);
        $result = $this->getJsonResponse();
        $this->assertError($result, 422);
        $this->assertErrorMessage($result, 'Validation failed');
    }
}
```

Useful assertions: `assertSuccess`, `assertError($result, $code)`, `assertErrorMessage`, `assertStatus`, `getJsonResponse`.

<a name="console-commands"></a>
## Console Commands

<a name="service-routes-command"></a>
### `service routes`

Prints all routes registered by a service — handy for verifying the action map, URL templates, and route collisions before going live.

```bash
bin/cake service routes <service>
```

Example:

```bash
bin/cake service routes articles
```

Output (as a console table):

```
+--------------+-------------+------------------+----------+--------+----------+
| Route name   | Method(s)   | URI template     | Service  | Action | Plugin   |
+--------------+-------------+------------------+----------+--------+----------+
|              | GET, POST   | /articles        | articles | index  |          |
|              | GET, PUT, DELETE | /articles/:id | articles | view   |          |
+--------------+-------------+------------------+----------+--------+----------+
```

Options:

- `--verbose` — adds a `Defaults` column with the full route defaults (JSON).
- `--sort` — sorts the route table by route name.
- `service` (required argument) — the service name to inspect.

The command also detects and warns about **possible route collisions** (same template + HTTP method matched by more than one route).

<a name="legacy-obsolete-components"></a>
## Legacy & Obsolete Components

The following components originate from the CakePHP 2/3 era. They are **kept** because consumers depend on them, but they are not part of the current request lifecycle:

| Component | Status |
| --- | --- |
| `CakeDC\Api\Service\Auth\Auth` (`allow`/`deny`) | Old Cake2-style auth layer. |
| `Middleware\RequestHandlerMiddleware` | Deprecated no-op subclass of `BodyParserMiddleware`; use `BodyParserMiddleware` directly. |

Do not remove these without a major-version compatibility plan.
