
## Actions


There a lot of situations when needed to implement api that is more complex then just CRUD.
In this case of course we can't use default fallback magic.

So in this case we should implement custom service.

###  Creating custom service and action

The application level service would supposed to be in App\Service namespace.
Plugins service would go to PluginName\Service namespace.

```php
namespace App\Service;

use CakeDC\Api\Service\CrudService as ApiService;

class BlogsService extends ApiService {

}
```

Such service based on api plugin configuration will accesible using /api/blogs/... url.

###  Regestering custom action in service

Of course needed to define what actions are supported by the service and inject them into the service router.

One way to do it - use intialize method in service class.

```php
    public function initialize()
    {
        parent::initialize(); 
        $this->mapAction('stats', \App\Service\Blogs\StatsAction::class, ['method' => ['GET'], 'mapCors' => true]);
    } 
```

###  Creating custom action

On previous step we reffer to StatsAction.
This action available by the /api/blogs/stats GET route.

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

