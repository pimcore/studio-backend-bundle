# Additional and Custom Attributes

Pimcore Studio Backend allows you to add additional data to response schemas.
Every response schema implements the `AdditionalAttributesInterface` and `AdditionalAttributesTrait` which allows you to add additional attributes to the schema.

Similarly to the additional attributes, you can also add custom attributes to the schema. These attributes contain mainly data used for the `tree` customization. 
Therefore as default, the custom attributes are available for the tree response schema. 
If you want to add custom attributes to another schema, you need to implement the `CustomAttributesTrait` in the schema.

## How to add additional and custom attributes
You need to register a subscriber to that specific schema where you can add the additional data.

Every schema implements its own event that you can subscribe to.
Every event implements the `AbstractPreResponseEvent` which allows to add the actual data, but also makes it possible to get the actual Schema out of the event with a type safe getter.

#### Example Subscriber
This subscriber adds an additional `isImage` attribute to an object of Image instance and adds a custom attribute to the schema.

These custom attributes would be used to display a custom key in the tree and add a custom CSS class to an element. 

```php
<?php

namespace App\EventSubscriber;

use Pimcore\Bundle\StudioBackendBundle\Asset\Event\PreResponse\AssetEvent;
use Pimcore\Bundle\StudioBackendBundle\Asset\Schema\Type\Image;
use Pimcore\Bundle\StudioBackendBundle\Element\Schema\CustomAttributes;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class AssetResponseSubscriber implements EventSubscriberInterface
{

    public static function getSubscribedEvents(): array
    {
        return [
            AssetEvent::EVENT_NAME => 'onAssetEvent',
        ];
    }

    public function onAssetEvent(AssetEvent $event): void
    {
        if ($event->getAsset() instanceof Image) {
            $event->addAdditionalAttribute('isImage', true);
        }
        
        $event->setCustomAttributes(
            new CustomAttributes(
                key: 'My Awesome Key',
                additionalCssClasses: ['my-awesome-css-class'],
            )
        );
    }
}

```
#### Example Event
```php
<?php
declare(strict_types=1);

namespace Pimcore\Bundle\StudioBackendBundle\Asset\Event\PreResponse;

use Pimcore\Bundle\StudioBackendBundle\Asset\Schema\Asset;
use Pimcore\Bundle\StudioBackendBundle\Element\Schema\CustomAttributes;
use Pimcore\Bundle\StudioBackendBundle\Event\AbstractPreResponseEvent;

final class AssetEvent extends AbstractPreResponseEvent
{
    public const EVENT_NAME = 'pre_response.asset';

    public function __construct(
        private readonly Asset $asset
    ) {
        parent::__construct($asset);
    }

    /**
     * Use this to get additional infos out of the response object
     */
    public function getAsset(): Asset
    {
        return $this->asset;
    }

    public function getCustomAttributes(): ?CustomAttributes
    {
        return $this->asset->getCustomAttributes();
    }

    public function setCustomAttributes(CustomAttributes $customAttributes): void
    {
        $this->asset->setCustomAttributes($customAttributes);
    }
}
```

### List of custom attributes

- `icon` - The custom icon that should be displayed in the tree.
- `tooltip`- The custom HTML tooltip to be displayed in the tree.
- `additionalIcons` - Array of additional icons that should be displayed in the tree.
- `key` - The key that should be displayed in the tree.
- `additionalCssClasses` - Additional CSS classes that should be added to the tree element.

### List of available events
- `pre_response.asset`
- `pre_response.asset_custom_metadata`
- `pre_response.asset_custom_settings`
- `pre_response.data_object`
- `pre_response.dependency`
- `pre_response.email.blocklist.entry`
- `pre_response.email.log.detail`
- `pre_response.email.logList.entry`
- `pre_response.email.log.detail.params`
- `pre_response.grid_column_configuration`
- `pre_response.grid_column_data`
- `pre_response.note`
- `pre_response.notification`
- `pre_response.element_property`
- `pre_response.predefined_property`
- `pre_response.user_detailed_role`
- `pre_response.role_tree_node`
- `pre_response.user_simple_role`
- `pre_response.schedule`
- `pre_response.tag`
- `pre_response.list_thumbnail`
- `pre_response.user`
- `pre_response.user_permission`
- `pre_response.user_tree_node`
- `pre_response.asset_version`
- `pre_response.data_object_version`
- `pre_response.document_version`
- `pre_response.image_version`
- `pre_response.version`