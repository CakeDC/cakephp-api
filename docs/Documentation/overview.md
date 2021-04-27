
## Services 

Service is central part of any API that concentrates on all features (operations) related to some application entity.
Service define list of actions (operations) that performed on an associated entity.
For example: for RESTful service, there could be  4 default operations defined using HTTP verbs for each of CRUD operations.

Each Service could be a separate class located in the src/Service folder, or the request could be passed through default FallbackService (which implements default behavior). 
Fallback service is defined by setting Api.ServiceFallback, and by default this is \CakeDC\Api\Service\FallbackService.

### Creating a Service


To create a new **Service**: first, extend the CakeDC\Api\Service\Service class. The name of your service class should be the name of the **Service**, followed by "Service" suffix. 

```php
namespace App\Service;

use CakeDC\Api\Service\Service;

class FooService extends Service {

	// your code

}
```

The filename should follow the same nomenclature. However, the location of the file itself depends upon your *versioning* strategy.

Service layer contemplates a versioned API if it is enabled by settings. Enabling it allows you to scale your **Services** by versioning them as your applications grows. The benefit of this isn't so obvious when developing your application internally, but becomes very important if you expose your service as an external API.

The *version* of your **Service** is determined by convention, this being the name of the subdirectory under ```app/Service/```, or in a plugin. For example, if the first version of your ```ExampleService``` were to be "v1", then you'd create file ```app/Service/v1/ExampleService.php``` in ```App\Service\v1``` namespace.


You can then create a new version of your **Service** by simply creating a new class under ```app/Service/v2/ExampleService.php```. **Services** may also be loaded from plugins using dot notation.

### Routing

Returning back to newly created FooService class. It must have declared ```loadRoutes``` method that defines this service behavior. Inside this method we should not use default Router class, because we should not rewrite the current cakephp router states while we analyze our service url. Instead, api plugin provide ```ApiRouter```.

Imagine we want to have a /foo/publish action that accepts HTTP POST requests.
In this case we would define the ```loadRoutes``` function.

```php

    public function loadRoutes()
    {
        ApiRouter::scope('/', $this->routerDefaultOptions(), function (RouteBuilder $routes) {
            $routes->extensions($this->_extensions);
            $options = [
                'map' => [
                    'publish' => ['action' => 'publish', 'method' => 'POST', 'path' => ''],
                ]
            ];
            $routes->resources($this->name(), $options);
        });
	}
```

### Action loading and mapping

Here, we will provide two strategies to load actions.

The first strategy is based on service location namespace.
In this case, all actions should be located in Action sub-namespace.

So if we have ```App\Service\FooService``` service and want to add ```publish``` action, then we should create the file ```App\Service\Action\FooPublishAction```. Here, ```Action```  belongs to namespace ```App\Service\Action``` and class defined is 3 parts: **Foo** is a camelized service name, **Publish** is a camelized action name, and **Action** is a suffix.

The second loading strategy could be achieved using ``` $_actionsClassMap ``` property. It contains a map of action names and full class names, eg.

```php
    protected $_actionsClassMap = [
        'index' => '\CakeDC\Api\Service\Action\CrudIndexAction',
        'view' => '\CakeDC\Api\Service\Action\CrudViewAction',
    ];
```

## Actions

Each service action defined extending Action class.

Action has execution life cycle.

There are two ways to action business logic.

First way is to define `action` method in user's action class, where arguments are named the same as input api endpoint parameters (similar to what was dones in the enterprise plugin).

Another way is to have action logic located in `execute` method. In this case, method does not accept any parameters the user should interact with. 


### Validation

If action requires input data validation, then it must overload ```validates()``` method that returns boolean result of validation, or could throw ```ValidationException```.  Methods like index, or view, do not require any validation in their lifecycle, and returns ```true``` by default.

NOTE:  action validation is not the same as model level validation. The purpose of action validation is to validate action input data and check the correctness and consistency. In case it is invalid, it would prevent stop action execution.

### Action flow events

Action.beforeExecute
Action.beforeProcess
Action.onAuth
Action.beforeValidate
Action.afterProcess

### Crud and Nested Crud Services. Fallback service.

Crud service defines actions and parameters for RESTful crud API.

Nested Crud service gentting parent params from routing system. If it is present, Nested extension will be loaded for all actions.

Falback service is default implementation of Nested Crud that defines routes for 1-level deep nesting. 



### Listing Service.

Listing service provides list of everything available in system services.

## Extensions

Actions are decorated by some functionality implemented during its life flow. Such decorators are called extensions and are provided by the api plugin.


## Metadata

Different extensions can return additional info that extends when it is returned by API data.
In this case, extensions append payload data into Result object, which is used by renderers to build final output.

This way extensions, like pagination or hateoas, inteact with caller.

## Request parser

A **Request parser** provides the logic required by a **Service** to resolve requests for input data.

Each **Service** class defines it's **request parser** in the ```parserClass``` options property, and by default is populated from the Api.parser setting.

## Renderers

A **Renderer** provides the logic required by a **Service** to process the response and handle errors..

Each **Service** class defines it's **renderer** in the ```rendererClass``` options property, and by default is populated from the Api.renderer setting.

Suported next renderers:

* Json - JSON object.
* JSend JSON object in JSend format. This is a default renderer that is configured in the config/api.php configuration file.
* Raw - returns data as it is provided.
* Xml - format result data as xml.

### JSend Response structure

Each JSend object on top level has result and data items.
Additionally all metadata is appended here too.

### Exceptions

#### Links 

Links is information about how current api endpoint relates to other endpoints.

crud actions are defined by links here:
* index - have links to add action.
* add - have links to index action.
* edit - have links to edit, delete and index actions.
* view - have links to edit, delete and index actions. Have links to index action of all nested services.

#### Pagination/

### Status Codes

By default the **Service** automatically handles many of the common errors in a request. The following are the status codes returned by the API.

* **401 Unauthorized:** If authentication is required but fails for the request.
* **403 Forbidden:** If a private method is requested or the action is blacklisted.
* **404 Not Found:** If the method is not defined on the **Service** class.
* **405 Method Not Allowed:** If an invalid HTTP method is used for the request.
* **409 Conflict:** If required arguments are missing from the request.
* **500 Internal Server Error:** If an error is thrown and not handled by the **Service**.
