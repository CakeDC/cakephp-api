# Middlewares

Middleware defined by plugin:

Initial `ApiMiddleware` was splited into parts to allow inject authorization middleware from Authorization plugin.

* ParseApiRequestMiddleware - perform url analyze and creates service instance and store it into request parameters.
* ProcessApiRequestMiddleware - perform api request.


`Api.Middleware` section in api.php defined set of middlewares loaded for the router.

By default it looks next way:

```
    'authentication' => [
        'class' => AuthenticationMiddleware::class,
        'request' => CakeDC\Api\ApiInitializer::class,
        'method' => 'getAuthenticationService',
    ],
    'apiParser' => [
        'class' => ParseApiRequestMiddleware::class,
    ],
    'apiAuthorize' => [
        'class' => AuthorizationMiddleware::class,
        'request' => ApiInitializer::class,
        'params' => [
            'unauthorizedHandler' => 'CakeDC/Api.ApiException',
        ],
    ],
    'apiAuthorizeRequest' => [
        'class' => RequestAuthorizationMiddleware::class,
    ],
    'apiProcessor' => [
        'class' => ProcessApiRequestMiddleware::class,
    ],
```

## Authentication
Defined schema allow user to use Authentication plugin for user authentication.

To override default \CakeDC\Api\ApiInitializer::getAuthenticationService autenticators
user should define class with same signatures and generates AuthenticationService with needed Identifiers and Authenticators.

## Authrization
Defined schema allow user to use Authorization plugin both for request authorizations and for code level checks. For this we loading RequestAuthorizationMiddleware and AuthorizationMiddleware.



## Installation

Rbac for authrize middlware setup performed by copy file cakephp-api/config/api_permissions.php.default to 
config/api_permissions.php and defining needed permissions.
By defaul it is allowed everything for unauthorized users.
