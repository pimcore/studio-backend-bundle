# Extending Updater and Patcher

Updating and patching elements should be done via the update and patch endpoints.
The payload of the endpoints are very flexible and can be sent partially.

Update adapters and patch adapters can be added via their respective interfaces and tagged with `pimcore.studio_backend.update_adapter` or `pimcore.studio_backend.patch_adapter`.

The updater is used for single element updates.
The patcher can be used for bulk updates.
Be aware if you have some kind of listings in your payload, like properties, if you use the updater you have to send the whole list and not only parts of it.
E.g. if you want to delete a property you have to send all properties except the one you want to delete in the updater payload.

## How does it work
Let's assume you want to update the parent of an asset and the payload you send looks like the following:
```json
{
    "parentId": 69
}
```

The `The UpdateService` will load all tagged adapters and call the `update` method if the element type is supported which is defined in the adapter itself
The adapters will then check if the index key is in the payload and updates the object.

## Example Update Adapter

```php
<?php
declare(strict_types=1);

namespace Pimcore\Bundle\StudioBackendBundle\Updater\Adapter;

use Pimcore\Model\Element\ElementInterface;
use function array_key_exists;

/**
 * @internal
 */
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
            'asset',
            'document',
            'object',
        ];
    }
}
```

## Example Patch Adapter

```php
<?php
declare(strict_types=1);

namespace Pimcore\Bundle\StudioBackendBundle\Patcher\Adapter;

use Pimcore\Bundle\StudioBackendBundle\Patcher\Service\Loader\PatchAdapterInterface;
use Pimcore\Bundle\StudioBackendBundle\Patcher\Service\Loader\TaggedIteratorAdapter;
use Pimcore\Model\Element\ElementInterface;
use function array_key_exists;

/**
 * @internal
 */
final readonly class ParentIdAdapter implements PatchAdapterInterface
{
    public function patch(ElementInterface $element, array $data): void
    {
        if (!array_key_exists($this->getIndexKey(), $data)) {
            return;
        }

        $element->setParentId($data[$this->getIndexKey()]);
    }

    public function getIndexKey(): string
    {
        'parentId';
    }

    public function supportedElementTypes(): array
    {
        return [
            'asset',
            'document',
            'object',
        ];
    }
}
 
```