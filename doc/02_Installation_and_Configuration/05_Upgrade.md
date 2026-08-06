# Upgrade Information

The following steps are necessary during updating to newer versions.

## Upgrade to 2026.3.0

### `RateLimiterInterface::check()` now expects `RateLimiterFactoryInterface`

Symfony 8.0 removes the autowiring aliases for the concrete
`Symfony\Component\RateLimiter\RateLimiterFactory`. All injections of that class in this bundle were
changed to `Symfony\Component\RateLimiter\RateLimiterFactoryInterface`, including the public
`Pimcore\Bundle\StudioBackendBundle\User\RateLimiter\RateLimiterInterface`:

```diff
-public function check(RateLimiterFactory $rateLimiterFactory): void;
+public function check(RateLimiterFactoryInterface $rateLimiterFactory): void;
```

**[Code BC break] If you implement `RateLimiterInterface` yourself, you have to widen the parameter type
as well** — keeping the concrete `RateLimiterFactory` violates parameter contravariance and PHP raises a
fatal error (`Declaration of ... must be compatible with ...`). The service is swappable via
`config/users.yaml`, so custom implementations are a supported scenario.

```php
use Symfony\Component\RateLimiter\RateLimiterFactoryInterface;

public function check(RateLimiterFactoryInterface $rateLimiterFactory): void
```

**Callers are not affected.** `RateLimiterFactory` implements `RateLimiterFactoryInterface`, so any code
passing a limiter factory into `check()` keeps working unchanged.

Behaviour is unchanged otherwise: `RateLimiterFactoryInterface` exists since Symfony 7.3 and its only
method, `create()`, is signature-identical to the concrete class's.

## Upgrade to 2025.4.6
- [GDPR Objects Export] Fixed: the data object export now includes inherited values (including inherited localized fields).

> **Note:** the structure of the exported data changed slightly to align with the Studio API data structure (e.g., relations are now exported as structured element data instead of plain `id`/`type` pairs).

## Upgrade to 2025.4.5
- [Grid Configuration] Fixed: Changed the length of `classId` column in `bundle_studio_grid_configurations` and `bundle_studio_grid_configuration_favorites` tables from 10 to 50 characters to support longer class IDs.

### Migration execution required
After upgrading, please execute the migration to apply the necessary database changes.

> **Note:** Grid configurations that were previously saved with a class ID longer than 10 characters have a truncated `classId` value in the database and will not be recovered by this migration. These configurations need to be deleted and re-saved after upgrading.