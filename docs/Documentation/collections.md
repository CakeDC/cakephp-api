Collections
==========

Collection actions allow to process many entities on the same endpoint call. 
This feature was created to allow easy integration with bulk actions, allowing to 
delete/edit/add many entities.

Adding collections to your own service is easy, use the `initialize()` method of your Service class.

* Use mapAction, map 3 routes to cover add/edit/delete

```php
$this->mapAction('collectionAdd', AddEditAction::class, [
    'method' => ['POST'],
    'mapCors' => true,
    'path' => 'collection/add'
]);
$this->mapAction('collectionEdit', AddEditAction::class, [
    'method' => ['POST'],
    'mapCors' => true,
    'path' => 'collection/edit'
]);
$this->mapAction('collectionDelete', DeleteAction::class, [
    'method' => ['POST'],
    'mapCors' => true,
    'path' => 'collection/delete'
]);
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