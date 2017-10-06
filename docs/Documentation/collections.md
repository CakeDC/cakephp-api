Collections
==========

Collections service and related actions allow to execute actions on 
many entities on the same endpoint call. This feature was created 
to allow easy integration with bulk actions, allowing to delete/edit/add
many entities.

There is a CollectionService class provided, in case you want to extend it,
but this feature will be possibly added to existing CrudService services.

Adding collections to your own service is easy

* Add routing

```php
    protected $_actionsClassMap = [
        'collectionAddEdit' => AddEditAction::class,
        'collectionDelete' => DeleteAction::class,
    ];

    /**
     * Initialize service level routes
     *
     * @return void
     */
    public function loadRoutes()
    {
        ApiRouter::scope('/' . $this->getName(), function (RouteBuilder $routes) {
            $routes->extensions($this->_routeExtensions);
            $routes->connect('/collection/add', [
                'controller' => $this->getName(),
                'action' => 'collectionAddEdit',
            ]);
            $routes->connect('/collection/edit', [
                'controller' => $this->getName(),
                'action' => 'collectionAddEdit',
            ]);
            $routes->connect('/collection/delete', [
                'controller' => $this->getName(),
                'action' => 'collectionDelete',
            ]);
        });
    }
```

If you need additional features, you can extend the specific actions 
and override the mapping configuration to use your own implementation.

* Using collection endpoints

Here are a couple examples, curl based

  * Edit post with id 15, and add a new post

```
    curl --request POST \
      --url http://collections.3dev/api/posts/collection/edit \
      --header 'content-type: multipart/form-data; boundary=---011000010111000001101001' \
      --form '0[title]=edit existing post title for id 15' \
      --form '1[title]=this is a new post' \
      --form '0[id]=15'
```
  
  * Delete 2 entities with id's 14 and 15
  
  ```
    curl --request POST \
      --url http://collections.3dev/api/posts/collection/delete \
      --header 'content-type: multipart/form-data; boundary=---011000010111000001101001' \
      --form '1[id]=14' \
      --form '0[id]=15' 
  ```