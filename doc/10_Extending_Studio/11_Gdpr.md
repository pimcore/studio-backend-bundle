# Extending GDPR Data Providers

The GDPR Data Provider system provides a centralized interface to find and export personal data from any part of your Pimcore application. You can add new data sources (like Data Objects, Assets, Users, or any custom entity) by creating your own provider.

New providers can be created by implementing the `Pimcore\Bundle\StudioBackendBundle\Gdpr\Provider\DataProviderInterface` and tagging your class as a service with `pimcore.studio_backend.gdpr_data_provider`.

## How does it work

As a developer, you only need to register it with the `pimcore.studio_backend.gdpr_data_provider` tag and implement the `DataProviderInterface`. The Pimcore system will automatically find your provider and use in Searching and Exporting.

### For Searching

This flow happens when a user opens the GDPR Data Extractor page and clicks "Search".

1.  **To build the page:** The user needs to create these methods on their newly created provider:

    -   `getName()`: To get the human-friendly name for the provider list.
    -   `getKey()`: To get the unique ID.
    -   `getSortPriority()`: To decide where to place your provider in the list.
    -   `getAvailableColumns()`: To build the columns for the search results grid.
    -   `getRequiredPermissions()`: One or more permissions required by user to access the data provider information
    -   `findData()`: Find the data in the particular provider using the searched terms
    -   `getDeleteSwaggerOperationId()`: The Operation ID to call to delete item

2.  **When the user clicks "Search":**
    -   The system first calls your `getRequiredPermissions()` method to check if the current user is allowed to use your provider.
    -   If permission is granted, the system calls your `findData()` method, passing the user's search terms.
    -   The result you return from `findData()` is then displayed directly in the results grid.

### For Exporting (Direct Download)

This flow happens when a user has already searched and clicks the "Export" button on a single item in your results grid.

1.  **When the user clicks "Export" on an item:**
    -   The system again checks your `getRequiredPermission()` method.
    -   If permission is granted, the system calls your `getSingleItemForDownload(int $id)` method, passing the ID of the item the user wants to export.
    -   The `array` or `object` you return from `getSingleItemForDownload()` is then automatically converted by the system into a **downloadable file** for the user.

---

### For Deleting an Item

This flow happens when a user clicks "Delete" on a result row.

Instead of handling deletion logic inside the provider, you simply **point** to the correct API endpoint.

1.  You implement `getDeleteSwaggerOperationId()`.
2.  This returns the unique **Operation ID** that handles deleting specific type of item.
3.  When the user confirms, the frontend calls that API endpoint using the item's ID.

## Configuration

The GDPR Data Extractor can be configured. The following options are available:

```yaml
pimcore_studio_backend:
    gdpr_data_extractor:
        dataObjects:
            classes:
                # Configure which classes should be considered
                # Array key is the class name
                Person:
                    allowDelete: true  # Allow delete of objects directly in preview grid (default: false)
                Customer:
                    allowDelete: false
        assets:
            types:
                # Configure which asset types should be considered
                - image
                - document
                - video
```

### Configuration Options

| Option                                                            | Type    | Default | Description                                                                                                |
|-------------------------------------------------------------------|---------|---------|------------------------------------------------------------------------------------------------------------|
| `gdpr_data_extractor.dataObjects.classes`                         | array   | `[]`    | Configure which Data Object classes should be considered for GDPR search. The array key is the class name. |
| `gdpr_data_extractor.dataObjects.classes.<ClassName>.allowDelete` | boolean | `false` | Allow deletion of objects directly in the preview grid.                                                    |
| `gdpr_data_extractor.assets.types`                                | array   | `[]`    | Configure which asset types should be considered for GDPR search (e.g., `image`, `document`, `video`).     |

## Example Data Provider

The example below shows some of the important functions with their implementations

```php

final class UserCreatedDataProvider implements DataProviderInterface
{
    public function getKey(): string
    {
        return 'key_value';
    }

    public function getName(): string
    {
        return 'Data Provider Name';
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

    public function getAvailableColumns(): array
    {
        return [
            new GdprDataColumn('column1', 'Column 1 Value'),
            new GdprDataColumn('column2', 'Column 2 Value'),
        ];
    }

    public function findData(?SearchTerms $terms): array
    {
       //Find user data using input $terms

        //return $results;
    }
    public function getDeleteSwaggerOperationId(): string
    {
        return 'data_provider_delete_by_operation_id';
    }

    public function getSingleItemForDownload(int $id): array|object
    {
     //   return single Item of a Data Provider
    }
}

```
