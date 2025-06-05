# Custom document type adapters

Custom document type adapters are used to process the document detail data before they are, e.g., saved to the database or displayed in the user interface.

Each custom document type has to be mapped to the corresponding adapter by its type.

## How to add a custom document type adapter

The following example shows how to implement a custom adapter for the `myCustom` document type.

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
use Pimcore\Bundle\StudioBackendBundle\Document\Data\SetterDataInterface;
use Pimcore\Bundle\StudioBackendBundle\Document\Data\DataNormalizerInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\AdapterLoader;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\Document\DocumentFieldKeys;
use Pimcore\Model\Document\MyCustomType;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

// Each document type adapter must be tagged with the `pimcore.studio_backend.document_type_adapter` tag
// It is possible to use the `AdapterLoader::DOCUMENT_TYPE_ADAPTER_TAG` enum for this purpose
// The adapter must implement at least the `SetterDataInterface` interface in order to be recognized by the system
#[AutoconfigureTag(AdapterLoader::DOCUMENT_TYPE_ADAPTER_TAG->value)]
final readonly class MyCustomAdapter implements SetterDataInterface, DataNormalizerInterface
{
    public function __construct(
        private ServiceResolverInterface $serviceResolver
    ) {
    }
    
    // We can use this method to process and modify any data before they are stored in the document.
    public function setData(Document $document, array $data, UserInterface $user): void
    {
        if (!$document instanceof MyCustomType) {
            return;
        }

        // You can pass your custom document detail data as "editableData" in the `data` array.
        if (!isset($data[DocumentFieldKeys::EDITABLE_DATA->value])) {
            return;
        }

        $editableData = $data[DocumentFieldKeys::EDITABLE_DATA->value];
        
        // In this example we assume that the `assetPath` editable data is a path to some asset.
        // We want to resolve this path to the actual asset element and set its ID in the document.
        $someDependantElementId = null;
        if (isset($editableData["assetPath"])) {
            $someAsset = $this->serviceResolver->getElementByPath('asset', $editableData["myCustomProperty"]);
            $someAssetId = $someAsset?->getId();
        }
        $document->setRelatedAsset($someAssetId);
        
        // We can also set other properties of the document based on the editable data.
        // The editable data can contain any custom data that you want to process.
        // Editable data should be passed as an array of key-value pairs, where the key is the property name.
        $document->setValues($editableData);
    }

    // Normalize method will fill the "documentDetailData" response array field, which is an empty array by default.
    public function normalize(Document $document): array
    {
        if (!$document instanceof MyCustomType) {
            return [];
        }

        $data = [];        
        // Add any necessary custom data based on your needs
        $data['someImportantValue'] = $document->getSomeImportantValue();
        $data['someOtherValue'] = $document->getSomeOtherValue();
        
        
        // Let's assume that the `myCustom` document type is returning only related asset ID.
        // However, we want to return multiple attributes of this asset.
        $data['relatedAsset'] = null;
        $assetId = $document->getSourceId();
        if (!$assetId) {
            return $data;
        }
        
        $asset = $this->serviceResolver->getElementById('asset', $assetId);
        if (!$asset) {
            return $data;
        }
        
        $data['relatedAsset'] = [
            'id' => $asset->getId(),
            'type' => $asset->getType(),
            'fileName' => $asset->getFilename(),
            'path' => $asset->getRealFullPath(),
        ];

        return $data;
    }
}
```

### 3. Add the mapping of the document type and the new adapter

```yaml
pimcore_studio_backend:
    document_type_adapter_mapping:
        App\Adapter\MyCustomAdapter: # The adapter class that should be used for processing of the document type data
            - "myCustom"  # The document type that should be processed by the adapter

```

Important interfaces:
- `Pimcore\Bundle\StudioBackendBundle\Document\Data\SetterDataInterface` - The mandatory interface that must be implemented by the adapter. 
- `Pimcore\Bundle\StudioBackendBundle\Document\Data\DataNormalizerInterface` - The interface that needs to be implemented if the adapter should be able to normalize and process custom document detail data.

Important data keys for update:
- `editableData` - The key for the detail data of the document (e.g., editable fields of the page document).
- `settingsData` - The key for the document settings data (e.g., document settings like prettyUrl).

:::info

Each adapter has to be tagged with the `pimcore.studio_backend.document_type_adapter` tag 
and has to implement the `Pimcore\Bundle\StudioBackendBundle\Document\Data\SetterDataInterface` interface.

:::