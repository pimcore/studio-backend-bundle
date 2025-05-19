# Extending via Events

The Pimcore Studio Backend provides a lot of events to hook into the system and extend the functionality. The events are dispatched via the Symfony event dispatcher and can be used to add custom logic to the system.

## Events
`pre_resolve.element_resolve` - This event is dispatched for the `pimcore_studio_api_elements_resolve` route to modify the resolve behavior of elements.

### Pre Resolve Event Example
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


#### Custom Attributes Example
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