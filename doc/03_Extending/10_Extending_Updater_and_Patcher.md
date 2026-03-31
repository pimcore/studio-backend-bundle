---
title: Extending Updater and Patcher
description: Add custom update and patch adapters for element save operations.
---

# Extending Updater and Patcher

The update and patch endpoints accept flexible, partial payloads for modifying elements.
Add custom adapters by implementing the respective interface and tagging the service.

| Adapter | Interface | Tag | Purpose |
|---------|-----------|-----|---------|
| Update | `UpdateAdapterInterface` | `pimcore.studio_backend.update_adapter` | Single element updates |
| Patch | `PatchAdapterInterface` | `pimcore.studio_backend.patch_adapter` | Bulk updates |

:::warning
The updater expects complete lists for array properties. For example, to remove a property, send all
properties *except* the one to delete. The patcher handles partial updates.
:::

## How It Works

When an update request arrives (e.g. `{"parentId": 69}`), the `UpdateService` loads all tagged
adapters and calls `update()` on each adapter that supports the element type. Each adapter checks
whether its index key exists in the payload and applies the change.

Example payload:
```json
{
    "parentId": 69
}
```

## Example Update Adapter

```php
<?php
declare(strict_types=1);

namespace App\Updater\Adapter;

use Pimcore\Model\Element\ElementInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementTypes;

#[AutoconfigureTag('pimcore.studio_backend.update_adapter')]
final readonly class ParentIdAdapter implements UpdateAdapterInterface
{
    public function update(ElementInterface $element, array $data): void
    {
        if (!array_key_exists($this->getIndexKey(), $data)) {
            return;
        }

        $element->setParentId($data[$this->getIndexKey()]);
    }

    public function getIndexKey(): string
    {
        return 'parentId';
    }

    public function supportedElementTypes(): array
    {
        return [
            ElementTypes::TYPE_ASSET,
            ElementTypes::TYPE_DOCUMENT,
            ElementTypes::TYPE_OBJECT,
        ];
    }
}
```

## Example Patch Adapter

```php
<?php
declare(strict_types=1);

namespace App\Patcher\Adapter;

use Pimcore\Bundle\StudioBackendBundle\Patcher\Service\Loader\PatchAdapterInterface;
use Pimcore\Model\Element\ElementInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementTypes;

#[AutoconfigureTag('pimcore.studio_backend.patch_adapter')]
final readonly class ParentIdAdapter implements PatchAdapterInterface
{
    public function patch(ElementInterface $element, array $data, UserInterface $user): void
    {
        if (!array_key_exists($this->getIndexKey(), $data)) {
            return;
        }

        $element->setParentId($data[$this->getIndexKey()]);
    }

    public function getIndexKey(): string
    {
        return 'parentId';
    }

    public function supportedElementTypes(): array
    {
        return [
            ElementTypes::TYPE_ASSET,
            ElementTypes::TYPE_DOCUMENT,
            ElementTypes::TYPE_OBJECT,
        ];
    }
}
 
```