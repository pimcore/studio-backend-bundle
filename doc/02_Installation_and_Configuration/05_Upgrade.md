# Upgrade Information

The following steps are necessary during updating to newer versions.

## Upgrade to 2026.4.0
- [Grid] Changed: `twigOperator` templates render in a dedicated Twig environment with the sandbox always enabled
  and without Pimcore's Twig extensions or the application's Twig configuration. Method calls and property access on
  objects are denied. Values are converted to plain data first: dates become ISO 8601 strings, consent values,
  `JsonSerializable` objects and enums become their data, other objects render as empty. `range()` returns at most
  1000 elements (best effort). See `doc/01_Architecture_Overview/01_Grid.md`.

> **Note:** `SandboxExtensionInitializer` implements the new `TwigOperatorEnvironmentProviderInterface`. A custom
> `SandboxExtensionInitializerInterface` implementation or decorator should implement it too: without it, templates keep
> rendering through the shared `twig` service with a deprecation, and fail if the returned sandbox is not registered
> there. `SandboxExtensionInitializer::initialize()` returns the isolated environment's sandbox, which is not registered
> on the shared `twig` service; render through `TemplateGeneratorInterface` instead. The initializer's
> `$blockedClasses`, `$allowedClasses` and `$hardBlockedMethods` arguments no longer apply, since all object access is
> denied. To add a filter, function or tag, tag a Twig extension with `pimcore_studio_backend.twig_operator_extension`
> and add its name to `sandbox_security_policy`.

- [Grid] Added: asset and data object grid rows carry an optional `score` (the search engine score of the hit, `null`
  without a scored query). The `Asset` and `DataObject` response schemas implement the new public
  `ScoreAwareInterface` (`getScore()`/`setScore()`); subclasses that already declare these methods must match the
  new signatures.

- [OpenAPI] Improved: `zircote/swagger-php` 6.x is now supported (`^5.0 || ^6.0`); the previous `>=5.6` conflict was removed. The generated Studio OpenAPI document is unchanged.

> **Note:** since swagger-php 5.6 an explicit `type:` or `ref:` on a `#[Property]` attribute no longer inherits the nullability of the PHP parameter it annotates. A property such as `#[Property(type: 'string')] private ?string $title` is emitted as `"type": "string"` instead of `"type": ["string", "null"]` once an installation resolves swagger-php >= 5.6. All Studio schemas now declare `nullable: true` explicitly. Bundles that register their own `open_api_scan_paths` must do the same for every PHP-nullable (or `mixed`) parameter whose attribute sets `type:` or `ref:`, otherwise their schemas silently lose `null` in the generated document and in clients generated from it. Properties without an explicit `type:` are not affected. The static `OpenApi\Generator::scan()` was removed in swagger-php 6.0; `OpenApiService` now uses `(new Generator())->generate()`.

- [Security] Added: two-factor authentication for the Studio login (authenticator app codes). See
  [Two-Factor Authentication](./07_Two_Factor_Authentication.md).
  - New configuration: `pimcore_studio_backend.two_factor_authentication.issuer` (default `Pimcore`) and
    `server_name` (default: the router's request context host, i.e. `framework.router.default_uri`, or `localhost`
    without it).
  - New rate limiter `studio_two_factor_code` (5 wrong codes per user in 5 minutes).
  - New endpoints: `POST /login/2fa`, `POST /user/two-factor/setup`, `POST /user/two-factor/confirm`,
    `DELETE /user/two-factor`, `DELETE /user/{id}/two-factor`. No change to `access_control` is needed.

> **Note:** `POST /login` changes for users with two-factor authentication required or enabled: it answers `200` with
> `{"twoFactorRequired": true, "twoFactorStep": "verify"|"setup"}` instead of an empty body, and the user is not logged
> in until the code is sent to `POST /login/2fa`. Clients that treat any `200` from `POST /login` as logged in break
> for these users only; users without two-factor authentication get the same empty `200` as before. Users who have
> `required` set but no authenticator app yet can no longer use Studio with the password alone: they set it up during
> the login. The login token (`POST /login/token`) still skips the code.

## Upgrade to 2026.3.2
- [Grid] Fixed: exporting an advanced column with a transformer filled empty localized source fields with the system
  default language, ignoring the configured fallback languages. The export now uses only the configured fallback
  languages, with or without a transformer. The interactive grid is unchanged. A missing source value now exports as
  an empty string instead of `"null"`.

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