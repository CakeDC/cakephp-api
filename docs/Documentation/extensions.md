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
An association is added only when the current user may access the `index` action of the related service with a GET request, as the permission rules decide; other associations are left out. The related service is the one named after the table class of the association's target (`AuthorsTable` → `authors`), also for an aliased association such as `Writers` with `className` `Authors`; a generic table is named after its database table. Every step of a dotted path is checked, and when no check can be made the association is embedded and a `debug` message is logged with the `api` scope. The check runs through the request's authorization service with the `ApiRbac::TARGET_ATTRIBUTE` request attribute, which names the service and action to check; a custom request policy has to honour that attribute, and so do callables and `Rule` objects that inspect the request, which otherwise see the current action. The same applies to a custom RBAC adapter, set with the `rbac` request attribute or the `adapter` setting of `RbacPolicy`: if it ignores the attribute, it checks the current action, and every requested association is embedded without a check. A `debug` message is logged when the request's `rbac` attribute is not an `ApiRbac`. Set the extension's `checkPermissions` setting to `false` to embed requested associations without this check.

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
Listen for `sort` param by default, but allow to override it with settings.
Accept sort options as JSON object.
Modify query for index action using ORM `orderBy()` method based on `sort`. Where data in sort params are JSON packed object with fields and orders as key and values. Orders can be one of 'asc' or 'desc'.
Invalid JSON is ignored. Entries with an invalid field or a non-string order are skipped. Only the object form is accepted: a JSON list such as `["title"]` or a numeric key is ignored, use `{"title": "asc"}`.
Supports the `sortableFields` setting, see Sort below.

* Sort
Purpose - index action.
Listen for `sort`, `direction` params by default, but allow to override it with settings.
Modify query for index action using ORM `orderBy()` method based `sort` and `direction` params.
An unknown `direction` falls back to 'asc'.

Settings:
- `sortField` - request param with the sort field. Default `sort`.
- `directionField` - request param with the sort direction. Default `direction`.
- `sortableFields` - list of allowed sort fields, for example `['Articles.title', 'Authors.last_name']`. When empty, only columns of the action table are allowed, as `field` or `Alias.field`. Other fields are ignored. An action that is not a CRUD action has no table, so it accepts only fields listed here. Columns the entity hides from output (`$_hidden`, such as password or token columns) are not sortable by default, because the row order would reveal their values; list them here to allow them.

Request sort fields are validated. A field is used only when it is a column of the action table that the entity does not hide or, when `sortableFields` is not empty, when it is listed there. Anything else is ignored, so a sort param can never add SQL to the query. Field names must match the column exactly, including case.

`sortableFields` replaces the default allowance rather than extending it. Once it is not empty only the listed fields are accepted, so list the action's own sortable columns as well:

```php
'Api' => [
    'Service' => [
        'articles' => [
            'Action' => [
                'Index' => [
                    'Extension' => [
                        'CakeDC/Api.Sort' => [
                            'sortableFields' => ['Articles.title', 'Articles.created', 'Authors.last_name'],
                        ],
                    ],
                ],
            ],
        ],
    ],
],
```

The action name key is camelized: use `Index`, not `index`.

To sort by an associated table field, add the field to `sortableFields`. The associated table must be joined in the main query, as belongsTo and hasOne associations are when contained (for example through CrudRelations). A hasMany association is loaded in a separate query, so its fields cannot be used for sorting.

Load only one of Sort and ExtendedSort for an action, because both read the `sort` param.
