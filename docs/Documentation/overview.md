
## Services

Service is central part of any API that concentrate all features(operations) realted to some application entity.
Service define list of actions (operations) that performed on associated entity.
For example, for RESTful service there could be described 4 default operations using HTTP verbs for each of CRUD operations.

Each Service could be an separate class located in src/Service folder, or request could be passed through default FallbackService that implements default behavior.
Fallback service defined by setting Api.ServiceFallback and by default this is \CakeDC\Api\Service\FallbackService.

### Service flow events

* Service.beforeDispatch
* Service.beforeProcess
* Service.afterDispatch


### Creating a Service

To create a new **Service** first extend the CakeDC\Api\Service\Service class. The name of your service class should be the name of the **Service**, followed by "Service" suffix.

```php
namespace App\Service;

use CakeDC\Api\Service\Service;

class FooService extends Service {

	// your code

}
```

The filename should follow the same nomenclature. However, the location of the file itself depends upon your *versioning* strategy.

Service layer contemplates a versioned API if it is enabled by settings. Enabling it allow you to scale your **Services** by versioning them as your applications grows. The benefit of this isn't so obvious when developing your application internally, yet becomes very important if you expose your service as an external API.

The *version* of your **Service** is determined by convention, this being the name of the subdirectory under ```app/Service/```, or in a plugin. For example, if the first version of your ```ExampleService``` were to be "v1", you'd create file ```app/Service/v1/ExampleService.php``` in ```App\Service\v1``` namespace.


You can then create a new version of your **Service** by simply creating a new class under ```app/Service/v2/ExampleService.php```. **Services** may also be loaded from plugins using dot notation.

### Routing

Returning back to newly created FooService class. It must have declared ```loadRoutes``` method that defines this service behavior. Inside this method we should not use default Router class, because we should not rewrite current cakephp router states during our service url analyze. Instead api plugin provide ```ApiRouter```.

Imagine we want to have /foo/publish action that should accept HTTP POST requests.
In this case we define next ```loadRoutes``` function.

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

There provided two strategies to load actions.

First strategy is based on service location namespace.
In this case all actions supposed to located in Action sub-namespace.

So if we have ```App\Service\FooService``` service and want to add ```publish``` action then we should create file ```App\Service\Action\FooPublishAction```, where ```Action``` that belongs to namespace ```App\Service\Action``` and class defined is 3 parts: **Foo** is a camelized service name, **Publish** is a camelized action name, and **Action** is a suffix.

Second loading strategy could be achieved used ``` $_actionsClassMap ``` property. It contains map of action names and full class names, eg.

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

First way is define `action` method in user's action class, where argument are named same as input api endpoint parameters. (like in was enterprise plugin).

In other case action logic should located in `execute` method. In this case method does not accept any parameters and user should interact with


### Validation

If action require input data validation it must overload ```validates()``` method that returns boolean result of validation or could throw ```ValidationException```. Such methods like index, or view obviously don't require any validation in it's lifecycle, and returns ```true``` by default.

Please note, that action validation is not the same as model level validation. The action validation purpose is to validate action input data and check it correctnes and consistence, and in case it is invalid prevent stop action execution.

### Action flow events

* Action.beforeProcess
* Action.onAuth
* Action.beforeValidate
* Action.beforeValidateStopped
* Action.validationFailed
* Action.beforeExecute
* Action.beforeExecuteStopped
* Action.afterProcess

### Crud and Nested Crud Services. Fallback service.

Crud service defines actions and parameters for RESTful crud API.

Nested Crud service gentting parent params from routing system and if it is presents loads Nested extension for all actions.

Fallback service is default implemeention of Nested Crud that defines routes for 1-level deep nesting.

Crud actions define some events that depend on the type of action and more details could be checked in documentation.

* Action.Crud.onPatchEntity (applied for add/edit actions)
* Action.Crud.onFindEntities (applied for index action)
* Action.Crud.afterFindEntities (applied for index action)
* Action.Crud.onFindEntity (applied for view action)


### Listing Service.

Listing service returns list of all available in system services.

## Extensions

Any action are decorated by some functionality it is implements during it life flow. Such decorators called extensions and provided with api plugin.


## Metadata

Different extension could return additional info that extends returned by API data.
In this case extension append payload data into Result object that used by renderers to build final output.

This way such extensions like pagination or hateoas inteact with caller.

## Request parser

A **Request parser** provides the logic required by a **Service** to resolve requests input data.

Each **Service** class defines it's **request parser** in the ```parserClass``` options property, and by default populated from Api.parser setting.

## Renderers

A **Renderer** provides the logic required by a **Service** to process the response and handle errors..

Each **Service** class defines it's **renderer** in the ```rendererClass``` options property, , and by default populated from Api.renderer setting.

Suported next renderers:

* Json - JSON object.
* JSend JSON object in JSend format. This is default renderer that configured in config/api.php configuration file.
* Raw - returns data as it is provided.
* Xml - format result data as xml.

### JSend Response structure

Each JSend object on top level has result and data items.
Additionally all metadata appended here too.

### Exceptions

#### Links

Links is a information how current api endpoint related with other endpoints.

If we will talk about crud actions there defined next links:
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
