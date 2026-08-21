# Upgrade Information

The following steps are necessary during updating to newer versions.

## Upgrade to 2025.4.7
- [User Management] Fixed: `GET /user/{id}` could time out or run out of memory when the user was referenced by a very large number of DataObjects (e.g. via a `User`-type class field), because the full, unbounded list of referencing objects was hydrated and embedded in every response. The `objectDependencies.dependencies` array on the `User` schema is now capped at 20 entries, and a new paginated `GET /user/{id}/object-dependencies` endpoint was added to browse the full list.

> **Note:** the `objectDependencies` schema itself has no breaking change - `dependencies` and `hasHidden` keep their existing names, types, and meaning; only a new, additive `totalItems` field was added. There is, however, a behavioral compatibility impact worth knowing about: `dependencies` previously contained the user's *complete* list of referencing objects, and now stops at 20. Any existing integration that assumed `dependencies` was exhaustive (rather than checking `totalItems`) will now silently see only the first 20 - such integrations must start reading `totalItems` and, if it exceeds `dependencies.length`, call the new paginated `GET /user/{id}/object-dependencies?page=&pageSize=` endpoint to get the rest. `hasHidden` is affected the same way: it now only reflects permission-denied objects within that 20-item window, not across the full list as before.

## Upgrade to 2025.4.6
- [GDPR Objects Export] Fixed: the data object export now includes inherited values (including inherited localized fields).

> **Note:** the structure of the exported data changed slightly to align with the Studio API data structure (e.g., relations are now exported as structured element data instead of plain `id`/`type` pairs).

## Upgrade to 2025.4.5
- [Grid Configuration] Fixed: Changed the length of `classId` column in `bundle_studio_grid_configurations` and `bundle_studio_grid_configuration_favorites` tables from 10 to 50 characters to support longer class IDs.

### Migration execution required
After upgrading, please execute the migration to apply the necessary database changes.

> **Note:** Grid configurations that were previously saved with a class ID longer than 10 characters have a truncated `classId` value in the database and will not be recovered by this migration. These configurations need to be deleted and re-saved after upgrading.