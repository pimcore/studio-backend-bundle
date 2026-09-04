# Upgrade Information

The following steps are necessary during updating to newer versions.

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