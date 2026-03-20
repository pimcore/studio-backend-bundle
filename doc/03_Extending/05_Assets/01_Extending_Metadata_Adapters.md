---
title: Asset Metadata Adapters
description: Create custom metadata adapters for asset types.
---

# Extending Metadata Adapters

Asset metadata adapters process metadata values before saving to the database or displaying in
the UI. Each metadata field maps to an adapter by its type.

## Adding a Custom Metadata Adapter

Implement a custom adapter for your metadata type. The example below creates an adapter for the
`myCustom` metadata type.

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

// Tag with `pimcore.studio_backend.metadata_adapter` (use the AdapterLoader enum).
// The adapter must implement at least `MetaDataAdapterInterface` so the system discovers it.
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
    
    // Normalize the `myCustom` metadata value to an array with only the required properties.
    public function normalize(mixed $value, string $type): mixed
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
- `Pimcore\Bundle\StudioBackendBundle\Metadata\Data\MetaDataAdapterInterface` - Main marker interface. Every adapter must implement this.
- `Pimcore\Bundle\StudioBackendBundle\Metadata\Data\DataNormalizerInterface` - Implement to normalize the metadata value for API responses.
- `Pimcore\Bundle\StudioBackendBundle\Metadata\Data\DataDenormalizerInterface` - Implement to denormalize incoming metadata values before saving.

:::info

Tag each adapter with `pimcore.studio_backend.metadata_adapter` so the system discovers it.

:::