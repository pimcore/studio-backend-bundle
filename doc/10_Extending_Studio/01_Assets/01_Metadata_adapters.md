# Extending metadata adapters

Asset metadata adapters are used to process metadata values before they are e.g. saved to the database or displayed in the user interface.

Each metadata field is mapped to the corresponding adapter by its type. This allows you to modify the metadata values in a flexible way.

## How to add a custom metadata adapters

In case of custom metadata types, it is possible to add custom adapters for their processing.

The following example shows how to implement a custom adapter for the `myCustom` metadata type.

### 1. Register your adapter

```yaml
services:
    App\Adapter\MyCustomAdapter: ~
```

### 2. Implement your adapter

```php
<?php
declare(strict_types=1);

namespace App\Adapter;

use Pimcore\Bundle\StaticResolverBundle\Models\Element\ServiceResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Metadata\Data\DataDenormalizerInterface;
use Pimcore\Bundle\StudioBackendBundle\Metadata\Data\DataNormalizerInterface;
use Pimcore\Bundle\StudioBackendBundle\Metadata\Data\MetaDataAdapterInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\AdapterLoader;
use Pimcore\Model\DataObject\MyCustomObject;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

// Each metadata adapter must be tagged with the `pimcore.studio_backend.metadata_adapter` tag
// It is possible to use the `AdapterLoader::METADATA_ADAPTER_TAG` enum for this purpose
// The adapter must implement at least the `MetaDataAdapterInterface` interface in order to be recognized by the system
#[AutoconfigureTag(AdapterLoader::METADATA_ADAPTER_TAG->value)]
final readonly class MyCustomAdapter implements 
    MetaDataAdapterInterface,
    DataNormalizerInterface,
    DataDenormalizerInterface
{
    
    public function __construct(
        private ServiceResolverInterface $serviceResolver
    ) {
    }
    
    // Let's assume that the `myCustom` metadata value is returning a specific object.
    // However, we want to return just some specific properties of this object.
    // We can therefore normalize the value to an array and return only the required properties.
    public function normalize(mixed $value, string $type): ?int
    {
        if (!$value instanceof MyCustomObject) {
            return null;
        }
        
        return [
            'id' => $value->getId(),
            'name' => $value->getName(),
            'key' => $value->getKey(),
            'someImportantValue' => $value->getSomeImportantValue(),
        ];
    }
    
    // In the denormalize method, we can convert the normalized array back to the original object
    public function denormalize(
        array $customMetadata,
        UserInterface $user,
        array $existingMetadata = [],
        bool $isPatch = false
    ): ?ElementInterface
    {
        $value = $customMetadata['data'] ?? null;
        if (!is_array($value) || empty($value['id'])) {
            return null;
        }

        $element = $this->serviceResolver->getElementById('object', $value['id']);
        if (!$element instanceof MyCustomObject) {
            return null;
        }
        
        return $element;
    }
}
```

### 3. Add the mapping of the metadata type and the new adapter

```yaml
pimcore_studio_backend:
    asset_metadata_adapter_mapping:
        App\Adapter\MyCustomAdapter: # The adapter class that should be used for processing the metadata
            - "myCustom"  # The metadata type that should be processed by the adapter

```

Important interfaces:
- `Pimcore\Bundle\StudioBackendBundle\Metadata\Data\MetaDataAdapterInterface` - The main marker interface that must be implemented by the adapter.
- `Pimcore\Bundle\StudioBackendBundle\Metadata\Data\DataNormalizerInterface` - The interface that needs to be implemented if the adapter should be able to normalize the metadata value.
- `Pimcore\Bundle\StudioBackendBundle\Metadata\Data\DataDenormalizerInterface` - The interface that needs to be implemented if the adapter should be able to denormalize the metadata value.

:::info

Each adapter has to be tagged with the `pimcore.studio_backend.metadata_adapter` tag in order to be recognized by the system.

:::