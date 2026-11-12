# Upgrade Information

The following steps are necessary during updating to newer versions.

## Upgrade to 2026.3.5
- [Data Objects] Fixed: `POST /data-objects/select-options` resolves the keys of a classification store, so select
  and multiselect keys with an options provider load their options. They are addressed as
  `<storeFieldName>.<groupId>.<keyId>`, or `<storeFieldName>.<groupId>.<language>.<keyId>` in the language comparison
  view. The provider receives the same owner context as on the initial layout load (`ownerType`, `ownerName`, `keyId`,
  `groupId`, `keyDefinition`). This needs `pimcore/studio-ui-bundle` 2026.3.5 or later, which sends the new field name.

> **Note:** a field name this endpoint cannot resolve now returns `422` with the parse error instead of `404`
> "Class with ID: ... not found". This applies to every field type, not only classification store keys. The context of
> the request stays in the nested `context` key of the options provider context and does not overwrite the keys above.

- [Data Objects] Fixed: the auto-save of a data object draft no longer writes the owner objects of a reverse object relation. These edits are applied only on an explicit save (and on updates without a task). The same applies to `PATCH` requests carrying an auto-save task.

> **Note:** edits of such a field that were skipped by an auto-save are not part of the stored draft, so reopening the draft cannot recover them; the client has to send them again with the explicit save. This also applies to custom data adapters that implement the public marker `RemoteStorageDataInterface`: they are skipped while an auto-save stores a draft, and in `POST /data-objects/select-options`. The marker is only checked on the adapter of a top-level field; adapters nested in localized fields, object bricks or field collections are not checked. `DataServiceInterface::updateEditableData()` gained an optional `bool $applyRemoteChanges = true` parameter; the default keeps the previous behavior.

## Upgrade to 2026.3.4
- [Translations] Fixed: the `admin` translation domain is maintained in Studio with the website languages, but the
  backend still used the admin UI languages for it. Columns of website languages that are no admin UI language
  (e.g. `fr_BE`) stayed empty and sorting by them failed. `POST /translations/list`, `POST /translations/export` and
  the CSV import now use the website translation languages the user is allowed to view for the `admin` domain, and the
  list only returns the values of these languages. Only the `studio` domain uses the admin UI languages.

> **Note:** this replaces the `admin` domain part of the 2025.4.15 entry below. As for the other website domains, a
> user without any valid website translation language receives `403` for list, export and import of the `admin` domain.

## Upgrade to 2026.3.3
- [Data Objects] Changed: the data object detail loads `manyToManyObjectRelation` and `manyToManyRelation` values of
  fields with `optimizedAdminLoading` without instantiating the related elements. Requires `pimcore/pimcore` 2026.3.3
  or later, which provides `LightweightRelationLoadingInterface`.
- [Documents] Added: `pimcore_studio_backend.documents.default_controllers` sets the controller of new page, snippet
  and email documents per document type (for documents created without a predefined document type). Types without an
  entry keep using `pimcore.documents.default_controller`, so existing installations are unaffected.
- [Grid] Fixed: block fields were silently dropped from the data object grid column configuration because no grid
  column definition existed for them. Block fields with `visibleGridView` enabled are now offered as grid columns and
  render through the existing block cell. Like field collections they are not sortable, filterable or exportable.
  A field type without a grid column definition is now logged at `warning` level instead of `info`.

## Upgrade to 2026.3.2
- [Permissions] Fixed: grids, advanced search, quick search and the element detail endpoints now require the `view`
  permission instead of `list`. Requires `pimcore/generic-data-index-bundle` 2026.3.2 or later, which limits the
  parent paths of a workspace to the `list` permission, and `pimcore/asset-metadata-class-definitions` 2026.3.2 or
  later, if installed.
  - Grids (data objects, assets, documents), advanced search, quick search and relation search dialogs only return
    elements the workspaces of the user allow to view. This also applies to grid export, folder batch patch and asset
    folder zip. Workspaces with `list` but without `view` therefore no longer show up there. The trees still show the
    parent folders of a workspace. The folders a tree PQL filter reveals only depend on elements the workspaces of the
    user allow to view.
  - `GET /data-objects/{id}`, `GET /documents/{id}` and `GET /assets/{id}` return `403` for elements the user may
    list but not view, and `404` for elements the user may not list. This check uses the core permissions, including
    workflow place permissions. Grids and search use the workspace permissions of the search index only.
    `GET /dependencies/{elementType}/{id}` requires `view` on the element in the same way.
  - Update endpoints (`PUT`) return the updated element, so for users with save or publish permission but without
    `view` the response is `403` or `404`. The update itself is applied as before.
  - Grids and advanced search return `404` for a folder the user may not list. In the grid, the asset metadata
    columns of type asset, document and object stay empty for a related element without `view`. The export still
    contains the path of that element.
- [Grid] Fixed: exporting an advanced column with a transformer filled empty localized source fields with the system
  default language, ignoring the configured fallback languages. The export now uses only the configured fallback
  languages, with or without a transformer. The interactive grid is unchanged. A missing source value now exports as
  an empty string instead of `"null"`.
- [Elements] Fixed: asynchronous batch edits, replacing usages and updating references after a copy removed
  unpublished related elements from the saved relations. Grid exports left them out. All of them now keep unpublished
  related elements, as the grid and the element editors do. Batch edits, replacing usages and updating references
  also no longer save localized fallback values into empty languages.

## Upgrade to 2026.3.0
- [Data Objects] Improved: every `inheritanceData.metaData` entry of the data object detail response (and the `inheritance` of a grid column) now carries two additional properties next to `objectId` and `inherited`:
  - `inheritable` (bool): whether the field type can take part in inheritance at all. It is `false` for field types whose `supportsInheritance()` returns `false` (e.g. `urlSlug`, `calculatedValue`, `fieldcollections`) and for field types without a Studio data adapter, so a client can tell an overridden value (`inherited: false, inheritable: true`) apart from a field that can never inherit (`inheritable: false`).
  - `inheritedValue` (mixed): the value the field inherits — or would inherit if its own value were removed — from the nearest ancestor that holds a non-empty value, normalized to the same shape as `objectData`. It is `null` when no ancestor holds a value, when the field is not inheritable, or when it was not requested: resolving it costs a walk up the tree for every field holding an own value, so it is opt-in. The data object detail response requests it; grid columns do not and always report `null`.

> **Note:** both properties are additive; `objectId` and `inherited` keep their meaning. `InheritanceServiceInterface::getInheritanceData()` gained a `bool $resolveInheritedValues = false` parameter and `getFieldInheritanceData()`, which returns the complete `InheritanceData` for a single field. The opt-in travels through the recursion as `FieldContextData::shouldResolveInheritedValue()` (constructor argument `resolveInheritedValue`). Custom `DataInheritanceInterface` adapters that build `InheritanceData` themselves should switch to `getFieldInheritanceData()` and pass `resolveInheritedValue` on to the `FieldContextData` they create for their child fields; instances they construct directly keep working and default to `inheritable: true`, `inheritedValue: null`.

## Upgrade to 2025.4.15
- [Translations] Fixed: website translations for locales that are not admin UI languages (e.g. `fr_BE`, `nl_BE`) could not be maintained. `POST /translations/list` only returned values for the available admin UI languages, so these columns stayed empty, and sorting by such a locale failed. The list now returns the languages the user is allowed to view for the requested domain (admin UI languages for the `admin` and `studio` domains), as in the classic admin UI.

> **Note:** this comes with the following behavioral changes for the translation list (`POST /translations/list`) and, where noted, the CSV export (`POST /translations/export`) and import:
> - Users with restricted website translation languages only receive values for their allowed languages, also for requests without `filters`.
> - Website translation languages configured on a user or role that are not valid system languages are ignored. A user without any valid website translation language left receives `403` for list, export and import (previously an empty result).
> - All `translationLike` column filters of a request are applied (previously only the first one). A `translationLike` filter on a language that is not available to the user returns `422`. The filter only applies to translation listings.
> - Additional sort filters on a locale are supported (previously failed with a database error).
> - The translation list no longer uses the core translation listing cache for the list of keys, as that cache does not take the requested languages into account. Translation values are still cached per key and language.

## Upgrade to 2025.4.13
- [Data Objects] Fixed: `POST /data-objects/select-options` failed with `Call to a member function getDataFromEditmode() on null` as soon as `changedData` contained unsaved localized fields. The endpoint decoded `changedData` with the classic editmode format (localized fields as language → attribute) while Studio sends its own data format (attribute → language). `changedData` is now applied through the same data adapters as a regular save, so it expects the Studio data format for every field type. Language edit permissions of non-admin users are now respected per language as well, instead of being matched against attribute names.

> **Note:** the internal `ApplyChangesHelper` (`DataObject\Legacy`) and its interface are deprecated and will be removed in 2027.1.0, since the select-options endpoint was their only consumer. They still expect the classic editmode data format; use `DataServiceInterface::updateEditableData()` with the Studio data format instead.

## Upgrade to 2025.4.7
- [User Management] Fixed: `GET /user/{id}` could time out or run out of memory when the user was referenced by a very large number of DataObjects (e.g. via a `User`-type class field), because the full, unbounded list of referencing objects was hydrated and embedded in every response. The `objectDependencies.dependencies` array on the `User` schema is now capped at 20 entries, and a new paginated `GET /user/{id}/object-dependencies` endpoint was added to browse the full list.

> **Note:** the `objectDependencies` schema itself has no breaking change - `dependencies` and `hasHidden` keep their existing names, types, and meaning. The new `totalItems` field is marked optional in the OpenAPI schema (even though it is always present in the actual response) specifically so that generated SDK types remain source-compatible with any existing code that constructs or mocks an `objectDependencies`-shaped value without it. There is, however, a behavioral compatibility impact worth knowing about: `dependencies` previously contained the user's *complete* list of referencing objects, and now stops at 20. Any existing integration that assumed `dependencies` was exhaustive (rather than checking `totalItems`) will now silently see only the first 20 - such integrations must start reading `totalItems` and, if it exceeds `dependencies.length`, call the new paginated `GET /user/{id}/object-dependencies?page=&pageSize=` endpoint to get the rest. `hasHidden` is affected the same way: it now only reflects permission-denied objects within that 20-item window, not across the full list as before. In both the preview and the new paginated endpoint, `totalItems` counts every referencing object regardless of the caller's view permission on it - it does not shrink to match what the caller can actually see, so a page (or the preview) can legitimately come back shorter than requested, or empty, while `totalItems` stays the same.

## Upgrade to 2025.4.6
- [GDPR Objects Export] Fixed: the data object export now includes inherited values (including inherited localized fields).

> **Note:** the structure of the exported data changed slightly to align with the Studio API data structure (e.g., relations are now exported as structured element data instead of plain `id`/`type` pairs).

## Upgrade to 2025.4.5
- [Grid Configuration] Fixed: Changed the length of `classId` column in `bundle_studio_grid_configurations` and `bundle_studio_grid_configuration_favorites` tables from 10 to 50 characters to support longer class IDs.

### Migration execution required
After upgrading, please execute the migration to apply the necessary database changes.

> **Note:** Grid configurations that were previously saved with a class ID longer than 10 characters have a truncated `classId` value in the database and will not be recovered by this migration. These configurations need to be deleted and re-saved after upgrading.
