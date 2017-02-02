Extensions
==========

All actions are decorated by some functionality, attached to their life flow. Such decorators called extensions and are provided with the API plugin.

* Cors
Purpose - all actions.
Purpose to provide access to the API from another domain. 
@todo implement configuration from extension config.

* CrudAutocompleteList 
Preparing lists for autocompleters on the client side.

* Nested
Purpose - all CRUD actions.
Provides filtering for (index and view) and patches form data (for add/edit requests) for nested services.

* CrudRelations
Purpose - index and view actions.
Listen for `include_relations` parameter and if it is present, add related models using ORM contain method to returned data.

* CrudHateoas
Purpose - all CRUD actions.
Metadata - `links`.
For each of CRUD actions populate `links` metadata (see metadata section in this document).
Each action have diferent links associated. They are similar to what we have in baked view pages.
So index action has a link to add page, and edit page has links to view, delete and index.
Additionaly if this service has nested services, links to them also will be appended in a similar way.

* Filter
Purpose - index action.
Listen for parameters named the same as field names and construct conditions. Allows diferent suffixes to field names to specify like or comparissons.
@todo discuss structures.

* FilterParameter
Purpose - index action.
Listen for `filters` parameter which describes complex queries as JSON object and construct condition. 
@todo discuss structures.

* CursorPaginate
Purpose - index action.
Listen for `count`, `max_id` or `since_id` params by default, but allow to override it with settings.
Provide paginate interface with `count`, `max_id` or by `count`, `since_id` parameters  that allow to have Twitter-style pagination for data that is actively added on the fly. Requires that table has field which is unique per item and ordered (it could use timestamp or numeric primary key).

* Paginate
Purpose - index action.
Metadata - `pagination`.
Listen for `page`, `limit` params by default, but allow to override these with settings.
Provides paginate interface with `page`, `limit` paramters as input.

* ExtendedSort
Purpose - index action.
Metadata - `pagination`.
Listen for `sort` param by default, but allow to override it with settings.
Accept sort options as JSON object.
Modify query for index action using ORM `order()` method based on `sort`. Where data in sort params are JSON packed object with fields and orders as key and values. Orders can be one of 'asc' or 'desc'.

* Sort
Purpose - index action.
Listen for `sort`, `direction` params by default, but allow to override it with settings.
Modify query for index action using ORM `order()` method based `sort` and `direction` params.
