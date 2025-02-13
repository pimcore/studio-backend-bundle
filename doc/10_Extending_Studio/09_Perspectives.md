# Extending Perspectives

Perspectives allow to create different views in the backend UI and even customize the standard perspective. They are using widgets to create unique views for the Pimcore Studio.

You can define:

- basic information like name, icon
- widgets on left, right and bottom
- limit the context menu permissions

## How to add a custom context menu permissions

To add a custom context menu permission to the Pimcore Studio Backend, you need to implement your custom event subscriber and inject the `Pimcore\Bundle\StudioBackendBundle\Perspective\Service\ContextMenuPermissionService` service.:

### 1. Define your subscriber service

```yaml
services:
  _defaults:
    autowire: true
    autoconfigure: true
    public: false

  App\EventSubscriber\StudioContextPermissionsSubscriber: ~
```

### 2. (Optional) Conditionally add your subscriber

If the Studio is not a required bundle, but you still want to add the permissions to it once its enabled, you can follow the example below:

- Add your service to the separate configuration yaml file, for example `pimcore_studio_backend.yaml`.
- In your bundle extension class, e.g. `AppExtension` add following to the [prepend](https://symfony.com/doc/current/components/dependency_injection/compilation.html#prepending-configuration-passed-to-the-extension) method:

```php
public function prepend(ContainerBuilder $container): void
{
    if ($container->hasExtension('pimcore_studio_backend')) {
        $loader = new YamlFileLoader(
            $container,
            new FileLocator(__DIR__ . '/../../config')
        );
        
        // Load your custom configuration file where you registered your Event Subscriber
        // In our example this is located in the `config` directory of your bundle
        $loader->load('pimcore_studio_backend.yaml');
    }
}
```

### 3. Implement your subscriber

```php
<?php
declare(strict_types=1);

namespace App\EventSubscriber;

use Pimcore\Bundle\StudioBackendBundle\Perspective\Model\ContextPermissionData;
use Pimcore\Bundle\StudioBackendBundle\Perspective\Service\ContextPermissionsServiceInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * @internal
 */
final readonly class StudioContextPermissionsSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private ContextPermissionsServiceInterface $permissionsService,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::CONTROLLER => 'addContextPermissions',
        ];
    }

    public function addContextPermissions(ControllerEvent $event): void
    {
        // Add your custom permission (key, group name and default value)
        $this->permissionsService->add(new ContextPermissionData('my_custom_permission_key', 'group', false));
    }
}
```