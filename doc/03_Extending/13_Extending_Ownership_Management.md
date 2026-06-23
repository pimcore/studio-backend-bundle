---
title: Ownership Management Providers
description: Register custom configuration types that can be administered in the Ownership Management area.
---

# Extending Ownership Management

The Ownership Management area is an **admin-only** screen that lets administrators list, reassign the
owner of (this is especially useful when the current owner does not exist in the system anymore),
and delete *user-owned configurations* across all users (for example, Grid Configurations or
Dashboards).

Add a new type by implementing `OwnershipProviderInterface` and tagging the service with
`pimcore.studio_backend.ownership_provider`. The implementation may live in **any bundle** that depends
on the Studio Backend Bundle. The Studio Backend auto-discovers tagged providers and exposes them through
the generic ownership-management endpoints — no new controllers or routes are required.

## How It Works

The generic endpoints resolve the provider by its `type` (taken from the request path) and delegate to it:

- `GET /ownership-management/types` lists the available tabs (one per provider).
- `POST /ownership-management/{type}/configurations` lists a type's configurations.
- `PUT  /ownership-management/{type}/owner` reassigns the owner.
- `DELETE /ownership-management/{type}/configurations` deletes configurations.

Implement these methods on your custom provider:

- `getType()`: Unique, machine-readable identifier used in the route (e.g. `grid_configuration`).
- `getLabel()`: Translation key (in the `studio` domain) for the tab label.
- `getIcon()`: Icon identifier for the tab.
- `getSortPriority()`: Higher values are listed first.
- `listConfigurations(OwnershipListQuery $query)`: Returns a `Collection` of `OwnershipConfiguration`.
- `reassignOwner(array $ids, int $newOwnerId)`: Reassigns the owner of the given configurations.
- `delete(array $ids)`: Deletes the given configurations.

### Listing

`listConfigurations()` receives an `OwnershipListQuery` and must return a
`Collection<OwnershipConfiguration>` (items for the current page plus the total count). The query exposes
everything a provider needs, already extracted from the request:

- `getOffset()` / `getLimit()`: pagination window.
- `getSearchTerm()`: a single free-text term to match against the configuration name, its id and the
  owner's username (`null` when no search is active).
- `includeDeletedOwners()`: when `false`, configurations whose owner no longer exists must be hidden
  (defaults to `true`).
- `getSortField()` / `getSortDirection()`: sorting.

Build each row with the `OwnershipConfigurationHydrator`.

### Reusing the in-memory filter

If your configurations reuse the [LocationAwareConfigRepository](https://docs.pimcore.com/platform/Pimcore/Development_Details/Configuration/Configuration_Environments/#configuration-storage-locations-and-fallbacks-locationawareconfigrepository), do **not** re-implement search, the deleted-owner filter, sorting, and pagination.
Hydrate all of your items and delegate to `InMemoryCollectionFilterInterface::apply()`, which performs the
whole pipeline for you.

### Owner reassignment and deletion (async)

`reassignOwner()` and `delete()` are reused for both the synchronous and asynchronous paths — you do not
implement anything extra for batching. The service runs a single id synchronously; for multiple ids it
creates a Generic Execution Engine job whose handlers call your provider per batch and report progress.
Both methods receive the configuration ids as `string[]`; cast them to your storage's id type internally.

## Example Provider

The example below is an in-memory provider that delegates listing to the shared filter. A real,
database-backed example is the `GridConfigurationProvider` in this bundle, and a configuration-file based
example is the `DashboardOwnershipProvider` in the `pimcore/studio-dashboards-bundle`.

```php
use Pimcore\Bundle\StudioBackendBundle\OwnershipManagement\Hydrator\OwnershipConfigurationHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\OwnershipManagement\Provider\OwnershipProviderInterface;
use Pimcore\Bundle\StudioBackendBundle\OwnershipManagement\Query\OwnershipListQuery;
use Pimcore\Bundle\StudioBackendBundle\OwnershipManagement\Service\Filter\InMemoryCollectionFilterInterface;
use Pimcore\Bundle\StudioBackendBundle\Response\Collection;

final readonly class MyConfigurationProvider implements OwnershipProviderInterface
{
    private const string TYPE = 'my_configuration';

    public function __construct(
        private MyConfigurationRepository $repository,
        private OwnershipConfigurationHydratorInterface $hydrator,
        private InMemoryCollectionFilterInterface $collectionFilter,
    ) {
    }

    public function getType(): string
    {
        return self::TYPE;
    }

    public function getLabel(): string
    {
        return 'ownership_management_type_my_configuration';
    }

    public function getIcon(): string
    {
        return 'settings';
    }

    public function getSortPriority(): int
    {
        return 10;
    }

    public function listConfigurations(OwnershipListQuery $query): Collection
    {
        $items = [];
        $listItems = $this->repository->findAll($query->getSearchTerm(), $query->getLimit(), $query->getOffset();
        foreach ($listItems) as $config) {
            $items[] = $this->hydrator->hydrate(
                (string) $config->getId(),
                self::TYPE,
                $config->getName(),
                $config->getOwner(),
            );
        }

        return $this->collectionFilter->apply($items, $query);
    }

    public function reassignOwner(array $ids, int $newOwnerId): void
    {
        foreach ($ids as $id) {
            $config = $this->repository->getById($id);
            $config->setOwner($newOwnerId);
            $this->repository->save($config);
        }
    }

    public function delete(array $ids): void
    {
        foreach ($ids as $id) {
            $this->repository->delete($this->repository->getById($id));
        }
    }
}
```

Register the provider and tag it:

```yaml
services:
    App\OwnershipManagement\MyConfigurationProvider:
        tags: [ 'pimcore.studio_backend.ownership_provider' ]
```

Finally, add the tab label translation key to the `studio` domain:

```yaml
# translations/studio.en.yaml
ownership_management_type_my_configuration: My Configurations
```
