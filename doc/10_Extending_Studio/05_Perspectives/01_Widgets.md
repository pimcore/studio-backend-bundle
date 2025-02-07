# Extending Widgets

Widgets are the main building blocks of the perspectives. They can be used to create unique views for the Pimcore Studio.

## How to add a custom widget type

To add a custom widget to the Pimcore Studio Backend, you first need to extend the symfony configuration like the following:

### Example

```yaml
pimcore_studio_backend:
    widget_types:
      - my_custom_widget_id
```

## How to add a custom widget configuration

The widget configuration utilizes the `LocationAwareConfigRepository` for storing the configuration. In the symfony tree the
storage location can be configured. Following values are possible:
- `symfony-config` - write configs as Symfony Config as YAML files to `/var/config/alternative_object_trees/custom_object_tree.yaml`
- `settings-store` - write configs to the SettingsStore
- `disabled` - do not allow to edit/write configs at all

Details also see [Pimcore Docs](https://pimcore.com/docs/platform/Pimcore/Deployment/Configuration_Environments/#configuration-storage-locations--fallbacks).

#### Example
```yaml
pimcore_studio_backend:
  config_location:
    element_tree_widgets:
      write_target:
        type: 'symfony-config'
        options:
            directory: '/var/www/html/var/config/element_tree_widgets'
```

To add a custom widget configuration, following steps need to be done:
1. Create a new custom **widget type**.
2. Create a new **repository** using `LocationAwareConfigRepository` and implementing the mandatory `WidgetConfigRepositoryInterface` and register the service with the `pimcore.studio_backend.widget_repository` tag.
3. Create a new **hydrator** which implements the mandatory `WidgetConfigHydratorInterface` and register the service with the `pimcore.studio_backend.widget_hydrator` tag.

### Example Widget Configuration Repository

TBD (maybe link the existing repository?)

### Example Widget Configuration Hydrator

```php
<?php
declare(strict_types=1);

namespace App\Perspective\Widget\Hydrator;

use App\Perspective\Widget\Model\CustomWidgetConfig;
use Pimcore\Bundle\StudioBackendBundle\Perspective\Model\WidgetConfigInterface;

final readonly class CustomWidgetConfigHydrator implements WidgetConfigHydratorInterface
{
    public function getSupportedWidgetType(): string
    {
        return 'my_custom_widget';
    }

    public function hydrate(array $widgetData): WidgetConfigInterface
    {
        return new CustomWidgetConfig(
            $widgetData['id'],
            $widgetData['name'],
            $widgetData['customField'],
            $widgetData['createdAt'],
            $widgetData['isWriteable']
        );
    }
}
```

:::info

Please note that the Hydrator must return a class implementing `WidgetConfigInterface` marker interface.

:::

Repository and Hydrator will be automatically registered and used by the Pimcore Studio Backend service to handle the widget configuration by defined type.