---
title: Custom Widget Types
description: Register custom perspective widget types with repository, hydrator, and schema.
---

# Extending Widgets

Widgets are the main building blocks of perspectives. Adding a custom widget type requires
three backend components: a **schema**, a **repository**, and a **hydrator**.

> **Working example:** The
> [studio-example-bundle](https://github.com/pimcore/studio-example-bundle)
> contains a complete `example_iframe` custom widget covering
> all steps below, including frontend integration.

---

## Registering a Widget Type

Register your widget type in the `pimcore_studio_backend` configuration. Load this config
via your bundle extension's `prepend()` method (since it targets another bundle's config key):

```yaml
# config/pimcore/studio_backend.yaml
pimcore_studio_backend:
    widget_types:
      - my_custom_widget_type
```

```php
// src/DependencyInjection/MyBundleExtension.php
public function prepend(ContainerBuilder $container): void
{
    if ($container->hasExtension('pimcore_studio_backend')) {
        $loader = new YamlFileLoader(
            $container,
            new FileLocator(__DIR__ . '/../../config')
        );
        $loader->load('pimcore/studio_backend.yaml');
    }
}
```

---

## Widget Config Schema

Create a class extending `Pimcore\Bundle\StudioBackendBundle\Perspective\Schema\WidgetConfig`
to define the data model for your widget. The base class provides `id`, `name`, `widgetType`,
and `icon` properties. Add any custom properties your widget needs.

```php
<?php
declare(strict_types=1);

namespace App\Perspective\Schema;

use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;
use Pimcore\Bundle\StudioBackendBundle\Perspective\Schema\WidgetConfig;
use Pimcore\Bundle\StudioBackendBundle\Response\ElementIcon;

#[Schema(
    title: 'Custom Widget',
    required: [
        'customField',
        'createdAt',
        'isWriteable',
    ],
    type: 'object'
)]
final class MyCustomWidget extends WidgetConfig
{
    public function __construct(
        string $id,
        string $name,
        ?ElementIcon $icon = null,
        #[Property(description: 'Custom Field', type: 'string', example: 'Some Value')]
        private readonly string $customField = 'Some Value',
        #[Property(description: 'Created At timestamp', type: 'int', example: 1738917191)]
        private readonly ?int $createdAt = null,
        #[Property(description: 'Is Writeable', type: 'bool', example: false)]
        private readonly bool $isWriteable = false,
    ) {
        parent::__construct($id, $name, 'my_custom_widget_type', $icon);
    }

    public function getCustomField(): string
    {
        return $this->customField;
    }

    public function getCreatedAt(): ?int
    {
        return $this->createdAt;
    }

    public function isWriteable(): bool
    {
        return $this->isWriteable;
    }
}
```

---

## Widget Config Repository

Create a class implementing `WidgetConfigRepositoryInterface` to handle CRUD operations for
your widget configurations. The `getSupportedWidgetType()` return value must match the type
string registered in the YAML config above.

### Key Methods

| Method | Purpose |
|--------|---------|
| `getSupportedWidgetType()` | Returns the widget type string |
| `isWidgetTypeOnlyWrapper()` | `true` if this repo wraps an existing config source |
| `createConfiguration()` | Creates a new widget config, returns the ID |
| `updateConfiguration()` | Updates an existing widget config |
| `getConfiguration()` | Returns a single config as array |
| `listConfigurations()` | Returns all configs as arrays |
| `deleteConfiguration()` | Deletes a widget config |

### Configuration Storage

The repository uses `LocationAwareConfigRepository` for persistent storage. Three backends
are available:

| Type | Best for | Description |
|------|----------|-------------|
| `settings-store` | Third-party bundles | Pimcore SettingsStore (database). Supports CRUD without container recompilation. Recommended for custom widgets. |
| `symfony-config` | Core / first-party | YAML files loaded into the container at compile time. Used by built-in `element_tree` widgets. Requires hooking into the bundle extension's `load()` to read configs from disk. |
| `disabled` | Read-only | No writes allowed. |

:::caution

The `symfony-config` backend relies on configs being loaded into the Symfony container at boot
time via `LocationAwareConfigRepository::loadSymfonyConfigFiles()`. Newly created configs are
only visible after a cache clear. For third-party bundles, use `settings-store` instead. It
reads directly from the database so new configs are available immediately.

:::

The storage location for `element_tree_widgets` (the built-in type) can be configured:

```yaml
pimcore_studio_backend:
  config_location:
    element_tree_widgets:
      write_target:
        type: 'symfony-config'
        options:
            directory: '/var/www/html/var/config/element_tree_widgets'
```

For details on storage location configuration, see
[Configuration Storage Locations](https://pimcore.com/docs/platform/Pimcore/Deployment/Configuration_Environments/#configuration-storage-locations--fallbacks).

### Wrapper Repositories

In some cases a custom configuration already includes all necessary properties and the data
can be used in the widget directly. Set `isWidgetTypeOnlyWrapper()` to return `true` to
indicate the repository only loads existing configs. In this case, `createConfiguration()`,
`updateConfiguration()`, and `deleteConfiguration()` should throw `NotWriteableException`.

### Example: Wrapper Repository

```php
<?php
declare(strict_types=1);

namespace App\Perspective\Widget\Repository;

use App\Custom\Configuration\Repository\ConfigurationRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotWriteableException;
use Pimcore\Bundle\StudioBackendBundle\Perspective\Repository\WidgetConfigRepositoryInterface;

final readonly class CustomWidgetConfigRepository implements WidgetConfigRepositoryInterface
{
    public function __construct(
        private ConfigurationRepositoryInterface $configurationRepository,
    ) {
    }

    public function getSupportedWidgetType(): string
    {
        return 'my_custom_widget_type';
    }

    public function isWidgetTypeOnlyWrapper(): bool
    {
        return true;
    }

    /**
     * @throws NotWriteableException
     */
    public function createConfiguration(array $widgetData): string
    {
        throw new NotWriteableException(
            'myType',
            'Creating new my_custom_widget_type via widgets is not supported.'
        );
    }

    /**
     * @throws NotWriteableException
     */
    public function updateConfiguration(array $widgetData): void
    {
        throw new NotWriteableException(
            'myType',
            'Updating my_custom_widget_type via widgets is not supported.'
        );
    }

    /**
     * @throws NotFoundException|NotWriteableException
     */
    public function getConfiguration(string $widgetId): array
    {
        $data = $this->configurationRepository->get($widgetId);

        if (!is_array($data) || $data[0] === null) {
            return [];
        }

        return $data;
    }

    /**
     * @throws NotFoundException|NotWriteableException
     */
    public function listConfigurations(): array
    {
        // Each item must include at least id, name, and icon
        return $this->configurationRepository->listConfigurations();
    }

    /**
     * @throws NotWriteableException
     */
    public function deleteConfiguration(string $widgetId): void
    {
        throw new NotWriteableException(
            'myType',
            'Deleting my_custom_widget_type via widgets is not supported.'
        );
    }
}
```

For a full CRUD implementation using settings-store, see
[IframeWidgetConfigRepository.php](https://github.com/pimcore/studio-example-bundle/blob/main/src/Perspective/Repository/IframeWidgetConfigRepository.php)
in the example bundle. For the built-in reference using symfony-config, see
`ElementTreeWidgetConfigRepository` in `studio-backend-bundle`.

---

## Widget Config Hydrator

Create a class implementing `WidgetConfigHydratorInterface`. The hydrator transforms raw
config arrays (from the repository) into your typed schema object. The
`getSupportedWidgetType()` return value must match the repository and YAML config.

```php
<?php
declare(strict_types=1);

namespace App\Perspective\Widget\Hydrator;

use App\Perspective\Schema\MyCustomWidget;
use Pimcore\Bundle\StudioBackendBundle\Perspective\Schema\WidgetConfig\WidgetConfigHydratorInterface;

final readonly class CustomWidgetConfigHydrator implements WidgetConfigHydratorInterface
{
    public function getSupportedWidgetType(): string
    {
        return 'my_custom_widget_type';
    }

    public function hydrate(array $widgetData): MyCustomWidget
    {
        return new MyCustomWidget(
            $widgetData['id'],
            $widgetData['name'],
            $widgetData['icon'],
            $widgetData['customField'],
            $widgetData['createdAt'],
            $widgetData['isWriteable']
        );
    }
}
```

:::info

The hydrator must return a class extending
`Pimcore\Bundle\StudioBackendBundle\Perspective\Schema\WidgetConfig`.

:::

---

## Service Registration

Register both services with their respective DI tags:

```yaml
services:
    App\Perspective\Widget\Repository\CustomWidgetConfigRepository:
        tags:
            - { name: pimcore.studio_backend.widget_repository }

    App\Perspective\Widget\Hydrator\CustomWidgetConfigHydrator:
        tags:
            - { name: pimcore.studio_backend.widget_hydrator }
```

The repository and hydrator are automatically discovered by the Studio Backend via their tags.

---

## Restrictions

- Widget configuration name must be 3-80 characters.
  Only alphanumeric characters, underscores, and spaces.
- Widget configuration ID is auto-generated by Pimcore Studio (UUID format).
- Every widget configuration must include at least `id`, `name`, and `icon` properties.
- When updating a widget, all configuration properties must be present in the request body.

---

## Element Tree Context Menu Permissions

Add custom context menu permissions for element tree widgets by implementing an event
subscriber that injects the `ContextPermissionsServiceInterface` service.

### 1. Define Your Subscriber Service

```yaml
services:
  _defaults:
    autowire: true
    autoconfigure: true
    public: false

  App\EventSubscriber\ElementTreeContextPermissionsSubscriber: ~
```

### 2. (Optional) Conditionally Add the Subscriber

If Studio Backend is not a required dependency, load the service definition only when the
bundle is active:

```php
public function prepend(ContainerBuilder $container): void
{
    if ($container->hasExtension('pimcore_studio_backend')) {
        $loader = new YamlFileLoader(
            $container,
            new FileLocator(__DIR__ . '/../../config')
        );
        $loader->load('pimcore_studio_backend.yaml');
    }
}
```

### 3. Implement the Subscriber

```php
<?php
declare(strict_types=1);

namespace App\EventSubscriber;

use Pimcore\Bundle\StudioBackendBundle\Element\Model\ContextPermissionData;
use Pimcore\Bundle\StudioBackendBundle\Element\Service\Permissions\ContextPermissionsServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementTypes;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\KernelEvents;

final readonly class ElementTreeContextPermissionsSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private ContextPermissionsServiceInterface $permissionsService,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::CONTROLLER => 'addDocumentContextPermissions',
        ];
    }

    public function addDocumentContextPermissions(): void
    {
        $this->permissionsService->add(
            new ContextPermissionData('addMyBundleContextAction', ElementTypes::TYPE_DOCUMENT, false)
        );
    }
}
```

---

## Example Widget Configuration (YAML)

```yaml
pimcore_studio_backend:
    element_tree_widgets:
        1efe7ac9_a03a_6334_9e48_13f662882599:
            id: 1efe7ac9_a03a_6334_9e48_13f662882599
            name: 'My Object Tree Widget'
            element_type: 'data-object' # data-object, asset or document types are supported
            icon:
                type: path
                value: 'path/to/config-icon.svg'
            root_folder: '/path/to/root/folder'
            show_root: true
            classes: ['CAR']
            pql: null # PQL query to filter the tree items
            page_size: 50 # define custom page size for your tree
            context_permissions:
                add: true
                addFolder: true
                changeChildrenSortBy: true
                copy: true
                cut: true
                delete: true
                lock: true
                lockAndPropagate: true
                paste: true
                publish: true
                reload: true
                rename: true
                searchAndMove: true
                unlock: true
                unlockAndPropagate: true
                unpublish: true
```

---

## Frontend Integration

To render your widget type in perspectives, register a matching React component in the
Studio UI's `WidgetRegistry`. See the
[Widget Manager plugin example](https://github.com/pimcore/studio-ui-bundle/blob/2026.x/doc/04_Extending/02_Plugin_Development_Examples/05_Use_the_Widget_Manager.md)
for frontend registration details, and the
[Adding Object Datatypes](https://github.com/pimcore/pimcore/blob/2026.x/doc/10_Extending_Pimcore/03_Custom_Extension_Guides/04_Adding_Object_Datatypes.md)
guide for an example of the full cross-layer pattern.
