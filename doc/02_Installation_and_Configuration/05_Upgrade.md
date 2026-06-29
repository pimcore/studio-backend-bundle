# Upgrade Information

The following steps are necessary during updating to newer versions.

## Upgrade to 2025.4.6
- [GDPR Objects Export] Fixed: the data object export now includes inherited values (including inherited localized fields).

> **Note:** the structure of the exported data changed slightly to align with the Studio API data structure (e.g., relations are now exported as structured element data instead of plain `id`/`type` pairs).

## Upgrade to 2025.4.5
- [Grid Configuration] Fixed: Changed the length of `classId` column in `bundle_studio_grid_configurations` and `bundle_studio_grid_configuration_favorites` tables from 10 to 50 characters to support longer class IDs.

### Migration execution required
After upgrading, please execute the migration to apply the necessary database changes.

> **Note:** Grid configurations that were previously saved with a class ID longer than 10 characters have a truncated `classId` value in the database and will not be recovered by this migration. These configurations need to be deleted and re-saved after upgrading.