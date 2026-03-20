# Extending GDPR Data Providers

The GDPR Data Provider system provides a centralized interface to find and export personal data from any part of your Pimcore application. You can add new data sources (like Data Objects, Assets, Users, or any custom entity) by creating your own provider.

New providers can be created by implementing the `Pimcore\Bundle\StudioBackendBundle\Gdpr\Provider\DataProviderInterface` and tagging your class as a service with `pimcore.studio_backend.gdpr_data_provider`.

## How does it work

As a developer, you only need to register it with the `pimcore.studio_backend.gdpr_data_provider` tag and implement the `DataProviderInterface`.
The Pimcore system will automatically find your provider and use it for searching and exporting.

### For Searching

This flow happens when a user opens the GDPR Data Extractor page and clicks "Search".

1.  **To build the page:** The user needs to create these methods on their newly created provider:

    -   `getName()`: To get the human-friendly name for the provider tab.
    -   `getKey()`: To get the unique ID.
    -   `getSortPriority()`: To decide where to place your provider in the list.
    -   `getRequiredPermissions()`: One or more permissions required by user to access the data provider information.
    -   `findData()`: Find the data in the particular provider using the searched terms.

2.  **When the user clicks "Search":**
    -   The system first calls your `getRequiredPermissions()` method to check if the current user is allowed to use your provider.
    -   If permission is granted, the system calls your `findData()` method, passing the user's search terms as a `FilterParameter`.
    -   The `Collection` you return from `findData()` is then displayed in the results grid.

> **Note:** The columns displayed in the search results grid are defined on the **frontend** side
> (in the tab component using TanStack Table's `createColumnHelper`). The backend only returns
> data rows via `GdprDataRow` — each row contains a key-value map that the frontend maps to columns.
> See the [GDPR Data Extractor documentation](https://docs.pimcore.com/platform/Pimcore/Content_Management_Features/GDPR_Data_Extractor)
> for the frontend implementation details.

### For Exporting (Direct Download)

This flow happens when a user has already searched and clicks the "Export" button on a single item in your results grid.

1.  **When the user clicks "Export" on an item:**
    -   The system again checks your `getRequiredPermissions()` method.
    -   If permission is granted, the system calls your `getSingleItemForDownload(int $id)` method, passing the ID of the item the user wants to export.
    -   The `array` or `Response` you return from `getSingleItemForDownload()` is then automatically converted by the system into a **downloadable file** for the user.

---


## Configuration

The GDPR Data Extractor can be configured. The following options are available:

```yaml
pimcore_studio_backend:
    gdpr_data_extractor:
        data_objects:
            classes:
                # Configure which classes should be considered
                # Array key is the class name
                Person:
                    allow_delete: true  # Allow delete of objects directly in preview grid (default: false)
                Customer:
                    allow_delete: false
        assets:
            types:
                # Configure which asset types should be considered
                - image
                - document
                - video
```

### Configuration Options

| Option                                                              | Type    | Default | Description                                                                                                |
|---------------------------------------------------------------------|---------|---------|------------------------------------------------------------------------------------------------------------|
| `gdpr_data_extractor.data_objects.classes`                          | array   | `[]`    | Configure which Data Object classes should be considered for GDPR search. The array key is the class name. |
| `gdpr_data_extractor.data_objects.classes.<ClassName>.allow_delete` | boolean | `false` | Allow deletion of objects directly in the preview grid.                                                    |
| `gdpr_data_extractor.assets.types`                                  | array   | `[]`    | Configure which asset types should be considered for GDPR search (e.g., `image`, `document`, `video`).     |

## Example Data Provider

The example below shows a minimal provider implementation. The constructor **must** accept
an `array $gdprConfig = []` parameter — a compiler pass injects the
`pimcore_studio_backend.gdpr_data_extractor` configuration into every tagged provider.

```php
use Pimcore\Bundle\StudioBackendBundle\Filter\MappedParameter\FilterParameter;
use Pimcore\Bundle\StudioBackendBundle\Gdpr\Provider\DataProviderInterface;
use Pimcore\Bundle\StudioBackendBundle\Gdpr\Schema\GdprDataRow;
use Pimcore\Bundle\StudioBackendBundle\Response\Collection;
use Symfony\Component\HttpFoundation\Response;

final class MyDataProvider implements DataProviderInterface
{
    public function __construct(
        array $gdprConfig = []
    ) {
    }

    public function getKey(): string
    {
        return 'my_data_provider';
    }

    public function getName(): string
    {
        return 'My Data Provider';
    }

    public function getSortPriority(): int
    {
        return 10;//set the priority of provider
    }

    /**
     * @return string[]
     */
    public function getRequiredPermissions(): array
    {
        // Return an array of permission strings
        return ['permission 1', 'permission 2'];//example : UserPermissions::USERS->value
    }

    public function findData(FilterParameter $filter): Collection
    {
        // Search your data source using $filter->getSearchTerm(), etc.

        return new Collection(
            totalItems: 1,
            items: [
                new GdprDataRow([
                    'id' => 123,
                    'name' => 'John Doe',
                    'email' => 'john@example.com',
                ])
            ]
        );
    }

    public function getSingleItemForDownload(int $id): array|Response
    {
        // Return the data for a single item to be exported as JSON
        return ['id' => $id, 'name' => 'John Doe', 'email' => 'john@example.com'];
    }
}
```

A complete working example (including the frontend tab component) is available in the
[Studio Example Bundle](https://github.com/pimcore/studio-example-bundle/tree/main/assets/js/src/examples/gdpr-data-extractor).
