## Services 

Service is central part of any API that concentrates on all features (operations) realted to some application entity.
Service define list of actions (operations) that is performed on an associated entity.
Example: for RESTful service, there could be 4 default operations using HTTP verbs for each of CRUD operations.

Each Service could be a separate class located in the src/Service folder, or a request could be passed through the default FallbackService that implements default behavior. 
Fallback service is defined by setting Api.ServiceFallback and by default this is \CakeDC\Api\Service\FallbackService.

## Define routes

### Define using `$_actionsClassMap`

The most trival way to define custom action namespace for CRUD action is using  $_actionsClassMap, which defines the map between action type and namespace.

```
    protected $_actionsClassMap = [
        'index' => '\App\Service\Blogs\IndexAction',
    ];
```

Redefining this variable - it is easy to disable default CRUD actions like DELETE.

### Define routes with `mapAction`.

More complex routes defined with mapAction method. This route accepts action's alias, action class name, and router config. The most logical way to define routes is the Service::intialize method.


Lets define /posts/:id/:flagtype route that accepts POST request.

```
use App\Posts\FlagAction;

...

    public function initialize()
    {
        parent::initialize(); 
        $this->mapAction('flag', FlagAction::class, [
            'method' => ['POST'],
            'mapCors' => true,
            'path' => ':id/:flag'
        ]); 
    }
```

In some cases you will want to define the route variable type. Perhaps you want to accept only two types of flags: 'spam' and 'inappropriate'.

In this case, we should define variable regex and pass it to router definition. 

```
    public function routerDefaultOptions()
    {
        $options = parent::routerDefaultOptions();
        $append = [
            'connectOptions' => [
                'flag' => 'spam|inappropriate',
            ]
        ];
        $options = Hash::merge($options, $append);

        return $options;
    } 

```

Defining router this way requests that other values in the flag field would be completely rejected and action execution would not happen.


## Accessing router variables values from action.

Access to variables defined in action router is very simple.

For actions that have :id param, the easiest way is to get the value from `$_id` property.

If you need to read additional parameters from parsed router, then you should read them from the parsed route data that is available from actions using the `getRoute()` method.

So if we want to read the flag type in action, we can do this:

```
    public function execute()
    {
        $flag = Hash::get($this->getRoute(), 'flag');
        ...
        
    }
```
