Extensions
==========

Any action are decorated by some functionality it is implements during it life flow. Such decorators called extensions and provided with api plugin.

* Cors
Purpose - all actions.
Purpose to provide access to api from another domain. 
@todo implements configuration from extension config.

* CrudAutocompleteList 
Preparing lists for autocompleters on client side.

* Nested
Purpose - all crud actions.
Provides filtering for (index and view) and patching form data (for add/edit requests) for nested services.

* CrudRelations
Purpose - index and view actions.
Listern for `include_relations` parameter and if it is presents add related models using ORM contain method to returned data.

* CrudHateoas
Purpose - all CRUD actions.
Metadata - `links`.
For each of CRUD actions populate `links` metadata (see metadata section in this document).
Each action have diferent links associated. They are similar to what we have in baked view pages.
So index action have link to add page, and edit page have links to view, delete and index.
Additionaly if this service has nested services links to them also will be appended in similar way.

* Filter
Purpose - index action.
Listern for parameter named same as field names and construct condition. Allowed diferent suffixed to field names to specify like or comparissons.
@todo discuss structures.

* FilterParameter
Purpose - index action.
Listern for `filters` parameter where described complex queries as json object and construct condition. 
@todo discuss structures.

* CursorPaginate
Purpose - index action.
Listern for `count`, `max_id` or `since_id` params by default, but allow to override it by settings.
Provide paginate interface with `count`, `max_id` or by `count`, `since_id` parameters  that allow to have twitter style pagination for data that actively add in time. Requires that table have field that unique per item and ordered (it could timestamp or numeric primary key).

* Paginate
Purpose - index action.
Metadata - `pagination`.
Listern for `page`, `limit` params by default, but allow to override it by settings.
Provide paginate interface with `page`, `limit` paramters as input.

* ExtendedSort
Purpose - index action.
Metadata - `pagination`.
Listern for `sort`, param by default, but allow to override it by settings.
Accept sort options as json object.
Modify query for index action using ORM order() method based `sort`. Where data in sort params are json packed object with fields and orders as key and values, where orders could one of 'asc' or 'desc'.

* Sort
Purpose - index action.
Listern for `sort`, `direction` params by default, but allow to override it by settings.
Modify query for index action using ORM order() method based `sort` and `direction` params.
