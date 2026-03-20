---
title: Extending via Events
description: Hook into Studio Backend API responses and element resolution via Symfony events.
---

# Extending via Events

The Studio Backend dispatches events via the Symfony EventDispatcher at key points in the API
response pipeline. Use these to modify responses, add custom data, or alter element resolution.

## ElementResolveEvent

The `pre_resolve.element_resolve` event fires on the element resolve endpoint
(`pimcore_studio_api_elements_resolve`). Use it to modify how elements are looked up by search term.

### Example: Resolve by Custom Field
```php
<?php
declare(strict_types=1);

namespace App\EventSubscriber;

use App\Model\Product\Car;
use Pimcore\Bundle\StudioBackendBundle\Element\Event\PreResolve\ElementResolveEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;


final readonly class DataObjectSearchModifierSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            ElementResolveEvent::EVENT_NAME => 'onElementFindBySearchTerm',
        ];
    }

    public function onElementFindBySearchTerm(ElementResolveEvent $event): void
    {
        if($event->getElementType() !== 'object') {
            return;
        }
        $searchTerm = $event->getSearchTerm();
        $modifiedSearchTerm = $this->modifySearchTerm($searchTerm);
        if($modifiedSearchTerm !== null) {
            $event->setModifiedSearchTerm((string)$modifiedSearchTerm);
        }
    }

    private function modifySearchTerm(string $searchTerm): null|string|int
    {

        return Car::getByUid($searchTerm, 1)?->getId();
    }
}
```


## PreResponse Events

PreResponse events fire before the API returns a response. Use them to add additional attributes
or set custom attributes (icons, tooltips, CSS classes) on response schemas.

For the full list of available events, see
[Additional and Custom Attributes](./01_Additional_and_Custom_Attributes.md).

### Example: Custom Attributes on Asset Response

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