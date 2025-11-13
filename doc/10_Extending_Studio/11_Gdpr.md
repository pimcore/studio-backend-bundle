# Extending GDPR Data Providers

The GDPR Data Provider system provides a centralized interface to find and export personal data from any part of your Pimcore application. You can add new data sources (like Data Objects, Assets, Users, or any custom entity) by creating your own provider.

New providers are created by implementing the `Pimcore\Bundle\StudioBackendBundle\Gdpr\Provider\DataProviderInterface` and tagging your class as a service with `pimcore.studio_backend.gdpr_data_provider` in gdpr.yaml.

If you're using the default service configuration, simply placing your class in the `src/Gdpr/Provider/` directory is all you need for it to be registered.

## How does it work

The `GdprManagerService` acts as the central coordinator for all registered providers. It automatically discovers your tagged service.

### 🔎 For Searching

1.  The manager loads all tagged providers to build the search interface. It calls your provider's `getName()`, `getKey()`, `getSortPriority()`, and `getAvailableColumns()` methods.
2.  When a user performs a search, the manager first checks `getRequiredPermission()` to see if the current user is allowed to use your provider.
3.  If permitted, the manager calls your provider's `findData()` method, passing the user's search terms. The results are then displayed in the grid.

### For Exporting (Direct Download)

The export process is a "direct download" flow.

1.  **Request:** The user makes a `GET` request to the export endpoint, specifying the item `id` in the URL and the `providerKey` as a query parameter.
    `GET /pimcore-studio/api/gdpr/export-data/1?providerKey=pimcore_users`
2.  **Logic:** The `GdprManagerService` resolves the one provider specified (`pimcore_users`).
3.  **Permission Check:** It calls your provider's `getRequiredPermission()` to check if the user is allowed.
4.  **Data Retrieval:** If permitted, the manager calls your provider's `getSingleItemForDownload(1)` method.
5.  **Response:** Your provider returns the raw data (like a DataObject or an array). The `GdprManagerService` automatically serializes this data into a downloadable JSON file, a "Save As..." dialog in the user's browser.

---

## Example Data Provider

Here is an example of a provider that supports both searching and direct exporting for **Customer** data objects.

```php
<?php
declare(strict_types=1);

namespace App\Gdpr\Provider;

// 1. Import all required classes
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Gdpr\Attribute\Request\SearchTerms;
use Pimcore\Bundle\StudioBackendBundle\Gdpr\Provider\DataProviderInterface;
use Pimcore\Bundle\StudioBackendBundle\Gdpr\Schema\GdprDataColumn;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\UserPermissions;
use Pimcore\Model\DataObject;
// You need to import the class for your DataObject
use Pimcore\Model\DataObject\Customer;

// 2. Add the AutoconfigureTag to register the provider
final class CustomerObjectProvider implements DataProviderInterface
{
    /**
     * You can inject any services you need.
     */
    public function __construct(
        // e.g. private readonly SecurityService $securityService
    ) {
    }

    /**
     * A unique key for your provider.
     */
    public function getKey(): string
    {
        return 'customers';
    }

    /**
     * A human-friendly name shown in the UI.
     */
    public function getName(): string
    {
        return 'Customer Objects';
    }

    /**
     * Sort order for the UI. Higher numbers appear first.
     */
    public function getSortPriority(): int
    {
        return 10;
    }

    /**
     * The general permission needed to use this provider.
     */
    public function getRequiredPermission(): UserPermissions
    {
        // Users must have 'objects' permission to use this provider
        return UserPermissions::OBJECTS;
    }

    /**
     * Defines the columns for the search result grid.
     * The 'key' must match the key in the array returned by findData().
     *
     * @return GdprDataColumn[]
     */
    public function getAvailableColumns(): array
    {
        return [
            new GdprDataColumn('id', 'ID'),
            new GdprDataColumn('email', 'Email Address'),
            new GdprDataColumn('path', 'Full Path'),
        ];
    }

    /**
     * The core search logic.
     *
     * @return array<array<string, mixed>>
     */
    public function findData(?SearchTerms $terms): array
    {
        // Note: $terms can be null
        if ($terms === null || empty($terms->value)) {
            return [];
        }

        $listing = new DataObject\Customer\Listing();
        $listing->setCondition('email LIKE ?', ['%' . $terms->value . '%']);
        $listing->load();

        $results = [];
        foreach ($listing as $customer) {
            // The keys here MUST match the keys in getAvailableColumns()
            $results[] = [
                'id' => $customer->getId(),
                'email' => $customer->getEmail(),
                'path' => $customer->getFullPath(),
            ];
        }

        return $results;
    }

    /**
     * Fetches a single item's data for export.
     * The returned data (array or object) will be serialized by the manager.
     *
     * @param int $id The ID of the item to fetch
     * @return array|object The data to be serialized
     *
     * @throws NotFoundException
     * @throws ForbiddenException
     */
    public function getSingleItemForDownload(int $id): array|object
    {
        // 1. Find the item
        $customer = Customer::getById($id);

        if ($customer === null) {
            throw new NotFoundException('Customer', $id);
        }

        // 2. (Optional) Check for specific permissions
        // if ($this->securityService->isAllowedToSee($customer) === false) {
        //     throw new ForbiddenException('You are not allowed to export this item.');
        // }

        // 3. Return the data.
        // The GdprManagerService will receive this and must serialize it.
        return $customer;

    }
}

```
