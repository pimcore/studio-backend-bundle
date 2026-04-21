---
title: Data Adapters
description: Custom adapters for saving, loading, exporting, and normalizing data object field data via the Studio Backend API.
---

# Data Adapters

Data adapters are the bridge between the Studio Backend API and Pimcore's data object field
types. Each field type (input, date, relation, etc.) has an adapter that controls how data is:

- **Saved** — transforming incoming API request data into the format the field definition
  expects before it is stored.
- **Read** — normalizing stored data into an API-friendly response format.
- **Detail page** — providing a detail-page-specific representation that may differ from the
  normalized value (falls back to normalization when not implemented).
- **Exported** — converting stored data into a string for grid/CSV export.
- **Inherited** — resolving inherited values in the data object hierarchy.
- **Previewed** — providing preview data for search results.

The Studio Backend ships adapters for all built-in Pimcore field types. If your custom field
type behaves similarly to a built-in type, you can **reuse an existing adapter** by mapping
your field type name to the same adapter class — no new PHP class required. See
[Built-in Adapters](#built-in-adapters) for the full list and
[Registration](#registration) for how to add the mapping.

:::note

Data adapters are one of several backend components needed when adding a custom data object
datatype. For the full cross-layer workflow (core registration, search index adapter, data
adapter, grid column definition, field definition resolver, and frontend dynamic types), see
the
[Adding Object Datatypes](https://github.com/pimcore/pimcore/blob/2026.x/doc/10_Extending_Pimcore/03_Custom_Extension_Guides/04_Adding_Object_Datatypes.md)
guide.

:::

---

## Interfaces

Every adapter must implement `SetterDataInterface`. The remaining four interfaces are optional
and can be added when your field type needs the corresponding capability.

| Interface | Required | Purpose |
|---|---|---|
| `SetterDataInterface` | **Yes** | Transform API request data for saving |
| `DataNormalizerInterface` | No | Customize the API response format |
| `DetailDataInterface` | No | Provide a detail-page-specific value (falls back to `DataNormalizerInterface`) |
| `DataExportInterface` | No | Provide a string representation for grid/CSV export |
| `DataInheritanceInterface` | No | Resolve inherited values in the object hierarchy |
| `SearchPreviewDataInterface` | No | Contribute preview data to search results |

All interfaces live in the `Pimcore\Bundle\StudioBackendBundle\DataObject\Data` namespace.

---

### SetterDataInterface (Required)

Every adapter must implement this interface. It defines how incoming API data is transformed
before being passed to the field's setter on the data object.

```php
namespace Pimcore\Bundle\StudioBackendBundle\DataObject\Data;

use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\Model\FieldContextData;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\UserInterface;

interface SetterDataInterface
{
    public function getDataForSetter(
        Concrete $element,
        Data $fieldDefinition,
        string $key,
        array $data,
        UserInterface $user,
        ?FieldContextData $contextData = null,
        bool $isPatch = false
    ): mixed;
}
```

| Parameter | Description |
|---|---|
| `$element` | The data object being saved. |
| `$fieldDefinition` | The Pimcore field definition for this field. |
| `$key` | The field name (key in the `$data` array). |
| `$data` | The full request data array. Access the field value via `$data[$key]`. |
| `$user` | The authenticated user performing the save. |
| `$contextData` | Container context (object brick, field collection, block, etc.). `null` for top-level fields. |
| `$isPatch` | `true` for PATCH requests. When patching, a `null` value typically means "don't change" rather than "set to null". |

The return value is passed directly to the field's setter on the data object. Return `null`
when the field should be cleared.

---

### DataNormalizerInterface

Implement this interface when the field's stored data needs custom transformation for API
responses. Without it, the raw value from the data object getter is returned as-is.

**When to use:**

- The stored value is an object (e.g., `Carbon`, `ElementInterface`) that needs to be
  converted to a serializable format (timestamp, structured array).
- You want to enrich the response with additional data (e.g., adding `fullPath` or
  `isPublished` to a relation).

```php
namespace Pimcore\Bundle\StudioBackendBundle\DataObject\Data;

use Pimcore\Model\DataObject\ClassDefinition\Data;

interface DataNormalizerInterface
{
    public function normalize(
        mixed $value,
        Data $fieldDefinition
    ): mixed;
}
```

| Parameter | Description |
|---|---|
| `$value` | The raw stored value from the data object getter. |
| `$fieldDefinition` | The Pimcore field definition for this field. |

Return the API-friendly representation of the value.

---

### DetailDataInterface

Implement this interface when the detail page of a data object should display a different
representation than the normalized value. When this interface
is **not** implemented, the system automatically falls back to
`DataNormalizerInterface::normalize()`.

**When to use:**

- The detail page requires a richer or more descriptive value than from `normalize()`.


```php
namespace Pimcore\Bundle\StudioBackendBundle\DataObject\Data;

use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\Model\FieldContextData;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\Concrete;

interface DetailDataInterface
{
    public function getDetailData(
        Concrete $object,
        mixed $value,
        Data $fieldDefinition,
        ?FieldContextData $contextData = null,
    ): mixed;
}
```

| Parameter | Description |
|---|---|
| `$object` | The data object being loaded for the detail page. |
| `$value` | The raw stored value from the data object getter. |
| `$fieldDefinition` | The Pimcore field definition for this field. |
| `$contextData` | Container context (object brick, localized field, etc.), or `null` for top-level fields. |

Return the detail-page-specific representation of the value.

---

### DataExportInterface

Implement this interface when the field type needs custom handling for grid column display
or CSV export. The return value is always a string.

**When to use:**

- The field's value cannot be meaningfully converted to a string by default (e.g., image
  galleries, encrypted fields, complex objects).
- You want to control exactly what appears in exported data.

```php
namespace Pimcore\Bundle\StudioBackendBundle\DataObject\Data;

use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\Model\FieldContextData;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\Concrete;

interface DataExportInterface
{
    public function getExportData(
        Concrete $object,
        Data $fieldDefinition,
        string $key,
        ?FieldContextData $contextData = null
    ): string;
}
```

| Parameter | Description |
|---|---|
| `$object` | The data object being exported. |
| `$fieldDefinition` | The Pimcore field definition for this field. |
| `$key` | The field name. |
| `$contextData` | Container context, or `null` for top-level fields. |

Return an empty string when the field has no exportable value.

---

### DataInheritanceInterface

Implement this interface when the field type participates in data object inheritance and
needs custom logic to determine if a value is inherited, overridden, or empty.

**When to use:**

- Container types (localized fields, object bricks, classification stores) that need to
  report inheritance status for their child fields.
- Field types with non-trivial inheritance semantics.

```php
namespace Pimcore\Bundle\StudioBackendBundle\DataObject\Data;

use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\Model\FieldContextData;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\Concrete;

interface DataInheritanceInterface
{
    public function getFieldInheritance(
        Concrete $object,
        Data $fieldDefinition,
        string $key,
        ?FieldContextData $contextData = null
    ): array;
}
```

| Parameter | Description |
|---|---|
| `$object` | The data object to check inheritance for. |
| `$fieldDefinition` | The Pimcore field definition for this field. |
| `$key` | The field name. |
| `$contextData` | Container context, or `null` for top-level fields. |

Return an array describing the inheritance state of the field and its children.

---

### SearchPreviewDataInterface

Implement this interface when the field type should contribute preview data to search
results. This controls what appears in quick-search and listing previews.

**When to use:**

- The field type has a natural preview representation (e.g., a date as a formatted timestamp,
  an image as a thumbnail URL).
- You want the field to appear in search result cards.

```php
namespace Pimcore\Bundle\StudioBackendBundle\DataObject\Data;

use Pimcore\Model\DataObject\ClassDefinition\Data;

interface SearchPreviewDataInterface
{
    public function getPreviewFieldData(
        mixed $value,
        Data $fieldDefinition,
        array $data
    ): array;
}
```

| Parameter | Description |
|---|---|
| `$value` | The raw stored value from the data object getter. |
| `$fieldDefinition` | The Pimcore field definition for this field. |
| `$data` | The accumulated preview data array. Add your field's preview entry and return it. |

Return the `$data` array with your field's preview entry added.

---

## Registration

Making the Studio Backend aware of your adapter requires two steps: tagging the service
and mapping it to one or more field type names.

### Step 1: Service Tag

Use the `#[AutoconfigureTag]` attribute on your adapter class to tag it with
`pimcore.studio_backend.data_adapter`:

```php
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('pimcore.studio_backend.data_adapter')]
final readonly class CustomFieldAdapter implements SetterDataInterface
{
    // ...
}
```

### Step 2: Configuration Mapping

Map your adapter class to the field type name(s) it handles. Create a config file in your
bundle and load it via the `prepend()` method of your bundle extension:

```yaml
# config/pimcore/studio_backend.yaml
pimcore_studio_backend:
    data_object_data_adapter_mapping:
        App\DataObject\Data\Adapter\CustomFieldAdapter:
            - "customFieldType"
```

```php
// src/DependencyInjection/MyBundleExtension.php
public function prepend(ContainerBuilder $container): void
{
    if ($container->hasExtension('pimcore_studio_backend')) {
        $loader = new YamlFileLoader(
            $container,
            new FileLocator(__DIR__ . '/../../config')
        );
        $loader->load('pimcore/studio_backend.yaml');
    }
}
```

:::note

A single adapter class can handle multiple field types. For example, the built-in
`StringAdapter` handles `input`, `textarea`, `email`, `firstname`, `lastname`, `password`,
`time`, and `wysiwyg`.

:::

---

## Complete Example

The following adapter implements all five interfaces for a hypothetical `customField` type:

```php
<?php
declare(strict_types=1);

namespace App\DataObject\Data\Adapter;

use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\DataExportInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\DataInheritanceInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\DataNormalizerInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\DetailDataInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\Model\FieldContextData;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\SearchPreviewDataInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\SetterDataInterface;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\UserInterface;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('pimcore.studio_backend.data_adapter')]
final readonly class CustomFieldAdapter implements
    SetterDataInterface,
    DataNormalizerInterface,
    DetailDataInterface,
    DataExportInterface,
    DataInheritanceInterface,
    SearchPreviewDataInterface
{
    // --- SetterDataInterface (required) ---

    public function getDataForSetter(
        Concrete $element,
        Data $fieldDefinition,
        string $key,
        array $data,
        UserInterface $user,
        ?FieldContextData $contextData = null,
        bool $isPatch = false
    ): string {
        // Access the incoming value from the request data array.
        $value = $data[$key] ?? null;

        if ($value === null && $isPatch) {
            // PATCH request: null means "don't change", not "clear the field".
            return $element->get($key);
        }

        // Transform the API value into the format the field definition expects.
        if (!is_string($value)) {
            $value = (string) $value;
        }
        
        return $value;
    }

    // --- DataNormalizerInterface (optional) ---

    public function normalize(mixed $value, Data $fieldDefinition): string
    {
        if ($value === null) {
            return null;
        }

        // Transform the stored value into the API response format.
        // Return any serializable structure (string, array, int, etc.).
        if (!is_string($value)) {
            $value = (string) $value;
        }
        
        return $value;
    }

    // --- DetailDataInterface (optional) ---

    public function getDetailData(
        Concrete $object,
        mixed $value,
        Data $fieldDefinition,
        ?FieldContextData $contextData = null,
    ): mixed {
        // Return a detail-page-specific representation of the value.
        // When this interface is not implemented, normalize() is called instead.
        return $value;
    }

    // --- DataExportInterface (optional) ---

    public function getExportData(
        Concrete $object,
        Data $fieldDefinition,
        string $key,
        ?FieldContextData $contextData = null
    ): string {
        $value = $object->get($key);

        if ($value === null) {
            return '';
        }

        // Return a string representation for grid/CSV export.
        return (string) $value;
    }

    // --- DataInheritanceInterface (optional) ---

    public function getFieldInheritance(
        Concrete $object,
        Data $fieldDefinition,
        string $key,
        ?FieldContextData $contextData = null
    ): array {
        // Return an array describing the inheritance state.
        // Check whether the value is set directly or inherited from a parent.
        return [];
    }

    // --- SearchPreviewDataInterface (optional) ---

    public function getPreviewFieldData(
        mixed $value,
        Data $fieldDefinition,
        array $data
    ): array {
        if ($value === null) {
            return $data;
        }

        // Add a preview entry for this field to the accumulated data array.
        $data[$fieldDefinition->getName()] = (string) $value;

        return $data;
    }
}
```

---

## Built-in Adapters

The following table lists all built-in adapters, the field types they handle, and which
optional interfaces they implement. If your custom field type behaves similarly to one of
these, you can reuse the existing adapter by adding a configuration mapping — no new PHP
class required.

| Adapter | Field Types | Interfaces |
|---|---|---|
| `StringAdapter` | `email`, `firstname`, `input`, `lastname`, `password`, `textarea`, `time`, `wysiwyg` | Setter |
| `BooleanAdapter` | `booleanSelect`, `checkbox` | Setter |
| `NumericAdapter` | `numeric` | Setter |
| `NumericRangeAdapter` | `numericRange` | Setter |
| `SliderAdapter` | `slider` | Setter |
| `SelectAdapter` | `country`, `gender`, `language`, `select` | Setter |
| `MultiSelectAdapter` | `countrymultiselect`, `languagemultiselect`, `multiselect` | Setter |
| `DateAdapter` | `date`, `datetime` | Setter, SearchPreview |
| `DateRangeAdapter` | `dateRange` | Setter, Normalizer |
| `LinkAdapter` | `link` | Setter, Normalizer |
| `RgbaColorAdapter` | `rgbaColor` | Setter, Normalizer |
| `ConsentAdapter` | `consent` | Setter, Normalizer |
| `UserAdapter` | `user` | Setter, Normalizer |
| `VideoAdapter` | `video` | Setter, Normalizer, SearchPreview |
| `ImageAdapter` | `image` | Setter, SearchPreview |
| `ExternalImageAdapter` | `externalImage` | Setter |
| `HotspotImageAdapter` | `hotspotimage` | Setter, Normalizer, SearchPreview |
| `ImageGalleryAdapter` | `imageGallery` | Setter, Normalizer, Export |
| `ManyToOneRelationAdapter` | `manyToOneRelation` | Setter, Normalizer |
| `ManyToManyRelationAdapter` | `manyToManyRelation` | Setter, Normalizer |
| `ManyToManyObjectRelationAdapter` | `manyToManyObjectRelation` | Setter, Normalizer |
| `AdvancedManyToManyRelationAdapter` | `advancedManyToManyRelation` | Setter, Normalizer |
| `AdvancedManyToManyObjectRelationAdapter` | `advancedManyToManyObjectRelation` | Setter, Normalizer |
| `ReverseObjectRelationAdapter` | `reverseObjectRelation` | Setter, Normalizer |
| `GeoPointAdapter` | `geopoint` | Setter |
| `GeoBoundsAdapter` | `geobounds` | Setter |
| `GeoPointsAdapter` | `geopolygon`, `geopolyline` | Setter |
| `QuantityValueAdapter` | `quantityValue` | Setter |
| `InputQuantityValueAdapter` | `inputQuantityValue` | Setter |
| `QuantityValueRangeAdapter` | `quantityValueRange` | Setter |
| `UrlSlugAdapter` | `urlSlug` | Setter |
| `CalculatedValueAdapter` | `calculatedValue` | Setter, Detail |
| `EncryptedFieldAdapter` | `encryptedField` | Setter, Normalizer, Export |
| `TableAdapter` | `table` | Setter, SearchPreview |
| `StructuredTableAdapter` | `structuredTable` | Setter, Normalizer, SearchPreview |
| `BlockAdapter` | `block` | Setter, Normalizer, SearchPreview |
| `FieldCollectionsAdapter` | `fieldcollections` | Setter, Normalizer, SearchPreview |
| `ObjectBricksAdapter` | `objectbricks` | Setter, Normalizer, Detail, Inheritance, SearchPreview |
| `LocalizedFieldsAdapter` | `localizedfields` | Setter, Normalizer, Detail, Inheritance, SearchPreview |
| `ClassificationStoreAdapter` | `classificationstore` | Setter, Normalizer, Detail, Inheritance, SearchPreview |

**Legend:** Setter = `SetterDataInterface`, Normalizer = `DataNormalizerInterface`,
Detail = `DetailDataInterface`, Export = `DataExportInterface`,
Inheritance = `DataInheritanceInterface`, SearchPreview = `SearchPreviewDataInterface`

:::info

To reuse an existing adapter for your custom field type, you only need to add a configuration
mapping. For example, if your custom field type stores and retrieves simple string values,
map it to `StringAdapter`:

```yaml
pimcore_studio_backend:
    data_object_data_adapter_mapping:
        Pimcore\Bundle\StudioBackendBundle\DataObject\Data\Adapter\StringAdapter:
            - "myCustomTextField"
```

The field type names are merged with the existing mapping for that adapter class, so the
built-in field types continue to work alongside your custom one. See
[Registration](#registration) for how to load this config in your bundle.

:::
