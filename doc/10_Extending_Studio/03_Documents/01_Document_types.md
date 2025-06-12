# Custom document types

There are by default six document types in Pimcore Studio:
- Email
- Folder
- Hardlink
- Link
- Page
- Snippet

## Custom document type adapters

Document type adapters are used to process the document detail data before they are, e.g., saved to the database or displayed in the user interface.

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

use App\Model\Editable\CustomEditableData;
use App\Model\Settings\CustomSettingsData;
use Pimcore\Bundle\StaticResolverBundle\Models\Element\ServiceResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Document\Data\EditableDataNormalizerInterface;
use Pimcore\Bundle\StudioBackendBundle\Document\Data\Model\SettingsDataInterface;
use Pimcore\Bundle\StudioBackendBundle\Document\Data\SetterDataInterface;
use Pimcore\Bundle\StudioBackendBundle\Document\Data\SettingsNormalizerInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\AdapterLoader;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\Document\DocumentFieldKeys;
use Pimcore\Model\Document\MyCustomType;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

// Each document type adapter must be tagged with the `pimcore.studio_backend.document_type_adapter` tag
// It is possible to use the `AdapterLoader::DOCUMENT_TYPE_ADAPTER_TAG` enum for this purpose
// The adapter must implement at least the `SetterDataInterface` interface in order to be recognized by the system
#[AutoconfigureTag(AdapterLoader::DOCUMENT_TYPE_ADAPTER_TAG->value)]
final readonly class MyCustomAdapter implements SetterDataInterface, SettingsNormalizerInterface
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
        
        // In this example, we assume that the `assetPath` editable data is a path to some asset.
        // We want to resolve this path to the actual asset element and set its ID in the document.
        $someDependantElementId = null;
        if (isset($editableData['assetPath'])) {
            $someAsset = $this->serviceResolver->getElementByPath('asset', $editableData['myCustomProperty']);
            $someAssetId = $someAsset?->getId();
        }
        $document->setRelatedAsset($someAssetId);
        
        // We can also set other properties of the document based on the editable data.
        // The editable data can contain any custom data that you want to process.
        // Editable data should be passed as an array of key-value pairs, where the key is the property name.
        $document->setValues($editableData);
        
        // Likewise, you can also pass custom document settings data as "settingsData" in the array of key-value pairs.
        if (!isset($data[DocumentFieldKeys::SETTINGS_DATA->value])) {
            return;
        }

        // This example converts `customUrl` settings data and sets it to a document.
        $settings = $data[DocumentFieldKeys::SETTINGS_DATA->value];
        $customUrl = $settings['customUrl'] ?? null;
        if ($customUrl !== null) {
            $settings['customUrl'] = htmlspecialchars($customUrl);
        }

        $document->setValues($settings);
    }

    // This method is used to normalize the settings data of the document before they are returned in detail endpoint.
    public function normalizeSettings(Document $document): ?SettingsDataInterface
    {
        if (!$document instanceof MyCustomType) {
            return null;
        }

        return new CustomSettingsData(
            $document->getPropertyOne(),
            $document->getPropertyTwo()
        );
    }


    // This method is used to normalize the editable data of the document before they are returned in detail endpoint.
    public function normalizeEditableData(Document $document): ?EditableDataNormalizerInterface
    {
        if (!$document instanceof MyCustomType) {
            return null;
        }        
        
        // Let's assume that the `myCustom` document type is returning only related asset ID.
        // However, we want to return multiple attributes of this asset.
        $relatedAssetData = null;
        $assetId = $document->getRelatedId();
        if ($assetId !== null) {
            $asset = $this->serviceResolver->getElementById('asset', $assetId);
            if ($asset !== null) {
                $relatedAssetData = [
                    'id' => $asset->getId(),
                    'type' => $asset->getType(),
                    'fileName' => $asset->getFilename(),
                    'path' => $asset->getRealFullPath(),
                ];
            }
        }      
        
        return new CustomEditableData(
            $document->getSomeImportantValue(),
            $document->getSomeOtherValue(),
            $relatedAssetData
        );
    }
}
```

Its important that your custom settings data model implements the `SettingsDataInterface` interface,
which is used by the `normalizeSettings` method.

```php
<?php
declare(strict_types=1);

namespace App\Model\Settings;

use Pimcore\Bundle\StudioBackendBundle\Document\Data\Model\SettingsDataInterface;

final readonly class CustomSettingsData implements SettingsDataInterface
{
    public function __construct(
        private ?int $customSettingOne,
        private ?string $customSettingTwo,
    ) {
    }
    
    public function getCustomSettingOne(): ?int
    {
        return $this->customSettingOne;
    }
    
    public function getCustomSettingTwo(): ?string
    {
        return $this->customSettingTwo;
    }
}
```

Its important that your custom editable data model implements the `EditableDataInterface` interface,
which is used by the `normalizeEditableData` method.

```php
<?php
declare(strict_types=1);

namespace App\Model\Editable;

use Pimcore\Bundle\StudioBackendBundle\Document\Data\Model\EditableDataInterface;

final readonly class CustomEditableData implements EditableDataInterface
{
    public function __construct(
        private ?int $someImportantValue,
        private ?string $someOtherValue,
        private ?array $relatedAssetData,
    ) {
    }
    
    public function getSomeImportantValue(): ?int
    {
        return $this->someImportantValue;
    }

    public function getSomeOtherValue(): ?string
    {
        return $this->someOtherValue;
    }

    public function getRelatedAssetData(): ?array
    {
        return $this->relatedAssetData;
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
- `Pimcore\Bundle\StudioBackendBundle\Document\Data\EditableDataNormalizerInterface` - The interface that needs to be implemented if the adapter should be able to normalize editable data.
- `Pimcore\Bundle\StudioBackendBundle\Document\Data\SettingsNormalizerInterface` - The interface that needs to be implemented if the adapter should be able to normalize document settings data.
- `Pimcore\Bundle\StudioBackendBundle\Document\Data\Model\EditableDataInterface` - Marker interface that needs to be implemented by custom editable data model. This interface is returned by the `normalizeEditableData` method of the `EditableNormalizerInterface`.
- `Pimcore\Bundle\StudioBackendBundle\Document\Data\Model\SettingsDataInterface` - Marker interface that needs to be implemented by custom settings data model. This interface is returned by the `normalizeSettings` method of the `SettingsNormalizerInterface`.

Important data keys:
- `editableData` - The key for the detail data of the document (e.g., editable fields of your document).
- `settingsData` - The key for the document settings data (e.g., document settings like title, description, prettyUrl).

:::info

Each adapter has to be tagged with the `pimcore.studio_backend.document_type_adapter` tag
and has to implement the `Pimcore\Bundle\StudioBackendBundle\Document\Data\SetterDataInterface` interface.

:::