## Services 

Service is central part of any API that concentrate all features(operations) realted to some application entity.
Service define list of actions (operations) that performed on associated entity.
For example, for RESTful service there could be described 4 default operations using HTTP verbs for each of CRUD operations.

Each Service could be an separate class located in src/Service folder, or request could be passed through default FallbackService that implements default behavior. 
Fallback service defined by setting Api.ServiceFallback and by default this is \CakeDC\Api\Service\FallbackService.

## Define routes

### Define using `$_actionsClassMap`

Most trival way to define custom action namespace for CRUD action is using of $_actionsClassMap that defines map between action type and namespace.

```
    protected $_actionsClassMap = [
        'index' => '\App\Service\Blogs\IndexAction',
    ];
```

Redefining this variable it is easy to disable default CRUD actions like DELETE.

### Define routes with `mapAction`.

More complex routes defined with mapAction method. that accept action's alias, action class name, and router config. Most logical way to define routes is Service::intialize method.


Lets define /posts/:id/:flagtype route that accept POST request.

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

In some cases you want to define route variable type. What if we want accept only two type of flags: 'spam' and 'inappropriate'.

In this case we should define variable regex and pass it to router definition. 

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

Defining router this way requests with other values in flag field would be completely rejected and action execution would not happen.


## Accessing router variables values from action.

Access to variables defined in action router very simple.

First of all in actions that has :id param, most obsious way is get this value from `$_id` property.

In case we need read additional parameters from parsed router he should read then from parsed route data available from actions using `getRoute()` method.

So if we want read flag type in action we could do next:

```
    public function execute()
    {
        $flag = Hash::get($this->getRoute(), 'flag');
        ...
        
    }
```
