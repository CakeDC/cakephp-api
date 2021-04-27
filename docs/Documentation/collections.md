Collections
==========

Collection actions allow us to process many entities on the same endpoint call. 
This feature was created to allow easy integration with bulk actions, as well as the ability to 
delete/edit/add many entities.

Adding collections to your own service is easy, use the `initialize()` method of your Service class.

* Use mapAction, map 3 routes to cover add/edit/delete

```php
$this->mapAction('bulkAdd', AddEditAction::class, [
    'method' => ['POST'],
    'mapCors' => true,
    'path' => 'bulk'
]);
$this->mapAction('bulkEdit', AddEditAction::class, [
    'method' => ['PUT'],
    'mapCors' => true,
    'path' => 'bulk'
]);
$this->mapAction('bulkDelete', DeleteAction::class, [
    'method' => ['DELETE'],
    'mapCors' => true,
    'path' => 'bulk'
]);
```

If you need additional features, you can extend the specific actions 
and override the mapping configuration to use your own implementation.

* Using collection endpoints

Here are a couple examples, curl based

  * Edit post with id 15, and add a new post

```
    curl --request POST \
      --url http://collections.3dev/api/posts/bulk \
      --header 'content-type: multipart/form-data; boundary=---011000010111000001101001' \
      --form '0[title]=edit existing post title for id 15' \
      --form '1[title]=this is a new post' \
      --form '0[id]=15'
```
  
  * Delete 2 entities with id's 14 and 15
  
  ```
    curl --request DELETE \
      --url http://collections.3dev/api/posts/bulk \
      --header 'content-type: multipart/form-data; boundary=---011000010111000001101001' \
      --form '1[id]=14' \
      --form '0[id]=15' 
  ```
