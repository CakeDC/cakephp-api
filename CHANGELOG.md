Changelog
=========

Releases for CakePHP 5.0
-------------

* 12.0.1
    * Security: refreshing a JWT issued before the second factor was completed returned tokens that no longer required 2FA; refresh now preserves the token audience.
    * Upgrade note: if 2FA is enabled, invalidate existing refresh tokens after upgrading, by emptying the `jwt_refresh_tokens` table or rotating `Api.Jwt.RefreshToken.secret`. Until you do, tokens that were already escalated before the upgrade keep their privileged audience: the access tokens stay valid for the rest of their lifetime, and their refresh tokens keep minting privileged ones. Rotating `Api.Jwt.AccessToken.secret` also ends current access tokens.
    * Upgrade note: in `JwtTokenTrait` a `null` token type now means a refresh and requires a payload with a non-empty string `aud`. `generateTokenResponse()`, `generateAccessToken()` and `generateRefreshToken()` called with a `null` type and no such payload throw a 401 `ValidationException` instead of issuing full-access tokens. Custom actions that issue new tokens must pass `'login'`, or `'2fa'` once the second factor is completed.
    * Security: the `sort` request parameter was passed unvalidated into `ORDER BY`, allowing SQL to be injected through the Sort extension, which is enabled by default, and through the ExtendedSort extension. In ExtendedSort the JSON payload was injectable through object keys, and also through array elements and numeric keys such as `["<expression>"]` or `{"0": "<expression>"}`, because a numeric key makes CakePHP emit the value verbatim. A sort field is now accepted only when it is a column of the action table, or, when the new `sortableFields` setting is used, only when it is listed there. Columns the entity hides from output, such as password or token columns, are accepted only when listed in `sortableFields`. An invalid sort direction falls back to `asc`.
    * Upgrade note: sorting by an associated table's field, by a computed select alias, or by a column the entity hides from output (`$_hidden`) now requires that field to be listed in the new `sortableFields` setting; previously it was accepted without any configuration. Unlisted fields are ignored rather than rejected, so those requests return HTTP 200 with unsorted results instead of an error - check your clients after upgrading. `sortableFields` replaces the default allowance rather than extending it: once it is non-empty only the listed fields are accepted, so list the action's own sortable columns alongside any associated table field, for example `'CakeDC/Api.Sort' => ['sortableFields' => ['id', 'created', 'Authors.first_name']]` under `Api.Service.<service>.Action.Index.Extension`. Sorting by an associated table field also requires that table to be joined in the main query. Field names must match the column exactly, including case. Malformed `sort` JSON in ExtendedSort is now ignored instead of producing an error response. ExtendedSort no longer accepts the JSON list form or numeric keys, such as `["title"]`; use the object form `{"title": "asc"}`. An index action that is not a CRUD action has no table columns to fall back to, so it sorts only by fields listed in `sortableFields`.
    * Security: a permission rule in `api_permissions.php` was checked key by key, and its `bypassAuth` or `allowed` key ended the check, so a rule that started with one of them matched every service, action, method and role. Every key listed after them was ignored. These two keys are now checked after the other keys of the rule, so such rules apply only to what they name and may stop matching. Rules that list them last, as the default file and the documentation do, behave as before.
    * Upgrade note: check `api_permissions.php` for rules where `allowed` or `bypassAuth` is not the last key, or that have other keys after it. Such rules used to match every request and now apply only to the roles, services, actions and methods they name. Keys after `allowed`/`bypassAuth` are now checked as conditions, including keys used as notes, such as `'note' => '...'`, which are matched against the user record.
    * Security: the CrudRelations extension, which is enabled by default, embedded any association a request named in `include_relations` (or every direct association with `include_direct`) without checking the permissions of the related service, so a user could read records of a service they may not access. An association is now embedded only when the user may access the `index` action of the related service with a GET request; other associations are left out of the response. The related service is the one named after the table class of the association's target, also for aliased associations, every step of a dotted path is checked, and nested routes such as `/authors/1/articles` are covered. Applications whose rules allow those services see no change. To embed associations without the check, set the CrudRelations setting `checkPermissions` to `false`.
    * Upgrade note: if clients use `include_relations`, `include_associations` or `include_direct`, an association is now left out of the response (still HTTP 200) unless the user may access the `index` action of the related service. Allow that action for the roles that need the embedded data, or restore the old behaviour with `'CakeDC/Api.CrudRelations' => ['checkPermissions' => false]` in `Api.Service.default.Action.default.Extension`, or per action under `Api.Service.<service>.Action.<action>.Extension`. An app with its own RBAC adapter (the `rbac` request attribute or the `RbacPolicy` `adapter` setting) must make it honour `ApiRbac::TARGET_ATTRIBUTE`; otherwise associations are still embedded without a check.
    * Security: the Log extension wrote the request data and the response to the `api` log as they were, so passwords, access and refresh tokens and API tokens ended up in `logs/api.log`. Values of keys containing `password`, `token`, `secret` or `authorization` are now replaced with `***`; the new `redact` setting adds words to that list. Plain objects and traversable values are redacted too, and the exception trace, which can hold call arguments, is no longer logged.

* 10.0.0
    * Remove deprecates
    * Only middleware initialization allowed since 10.0
    * Cleanup auth layer. Remove inbuilt authentication and authorization. Using cakephp/authentication and cakephp/authorization plugins middlewares.

* 9.0.0
    Initial version that support CakePHP 4.3


Releases for CakePHP 4.0
-------------

* 8.0.4
 * Add helper auth method Action::getIdentity

* 8.0.3
 * Abitility to disable extension

* 6.0.1
  * Fix deprecation messages

* 1.0.0-beta.2
  * Implements api middleware with PSR-7 support

* 1.0.0-beta.1
  * Initial release
