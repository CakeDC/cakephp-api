##Dependency Injection

The CakePHP service container enables you to manage class dependencies for your application services through dependency injection. Dependency injection automatically “injects” an Action's dependencies via the constructor without having to manually instantiate them.

### Api plugin preconfiguration for Dependency Injection

First of all, needs to load ContainerInjector middleware from App\Application class.

```php
class Application extends BaseApplication
{

    public function middleware(MiddlewareQueue $middleware): MiddlewareQueue
    {
        $middleware
            // ....
            ->add(new ContainerInjectorMiddleware($this->getContainer()))
            // ....
        ;
    }
```

