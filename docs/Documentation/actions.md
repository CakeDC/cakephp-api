
## Actions


There are many situations where we are required to implement api that is more complex then just CRUD.
In this case of course, we can't use default fallback magic.

Here, we should implement custom service.

###  Creating custom service and action

The application level service should be in App\Service namespace.
Plugins service would go to PluginName\Service namespace.

```php
namespace App\Service;

use CakeDC\Api\Service\CrudService as ApiService;

class BlogsService extends ApiService {

}
```

These services based on api plugin configuration will accesible using /api/blogs/... url.

###  Regestering custom action in service

It is important to define which actions are supported by the service and inject them into the service router.

One way to do it is to use the intialize method in service class.

```php
    public function initialize()
    {
        parent::initialize(); 
        $this->mapAction('stats', \App\Service\Blogs\StatsAction::class, ['method' => ['GET'], 'mapCors' => true]);
    } 
```

###  Creating custom action

On the previous step we reffered to StatsAction.
This action is available with the /api/blogs/stats GET route.

Let's define it here:


```php
namespace App\Api\Service\Action\Stats;

use CakeDC\Api\Exception\ValidationException;
use CakeDC\Api\Service\Action\Action;

class StatsAction extends Action
{

    public function validates()
    {
        return true;
    }

    public function execute()
    {
        return [
            'stats' => 'everything good'
        ];
    }

} 
```

