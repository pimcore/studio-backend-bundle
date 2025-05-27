# Custom document types

There are by default six document types in Pimcore Studio:
- Email
- Folder
- Hardlink
- Link
- Page
- Snippet

## Creating custom document types
You can extend the Studio with custom document types by creating a valid `Schema` and `EventSubscriber` which adds this schema to OpenApi specifications.

### Example custom document type schema
It is recommended to extend the `Pimcore\Bundle\StudioBackendBundle\Document\Schema\Document` class for your custom document type schema.
This ensures that your custom document type inherits all the necessary properties and methods, including additional attributes.

```php
<?php
declare(strict_types=1);

namespace App\Document\Schema\Type;

use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;
use Pimcore\Bundle\StudioBackendBundle\Document\Schema\Document;
use Pimcore\Bundle\StudioBackendBundle\Document\Schema\DocumentPermissions;
use Pimcore\Bundle\StudioBackendBundle\Response\ElementIcon;

#[Schema(
    title: 'CustomType',
    required: ['someProperty'],
    type: 'object'
)]
final class CustomType extends Document
{
    public function __construct(
        #[Property(description: 'Some Property', type: 'string', example: 'Some string value')]
        private readonly string $someProperty,
        string $fullPath,
        bool $published,
        string $type,
        string $key,
        bool $hasChildren,
        bool $hasWorkflowWithPermissions,
        DocumentPermissions $permissions,
        int $id,
        int $parentId,
        string $path,
        ElementIcon $icon,
        int $userOwner,
        ?int $userModification,
        ?string $locked,
        bool $isLocked,
        ?int $creationDate,
        ?int $modificationDate,
    ) {
        parent::__construct(
            $fullPath,
            $published,
            $type,
            $key,
            $hasChildren,
            $hasWorkflowWithPermissions,
            $permissions,
            $id,
            $parentId,
            $path,
            $icon,
            $userOwner,
            $userModification,
            $locked,
            $isLocked,
            $creationDate,
            $modificationDate
        );
    }

    public function getSomeProperty(): string
    {
        return $this->someProperty;
    }
}
```

### Example event subscriber
Event subscriber should listen to the `DocumentTypeSchemasEvent::EVENT_NAME` and add your custom document type schema to the OpenApi specifications.

```php
<?php
declare(strict_types=1);

namespace App\EventSubscriber;

use OpenApi\Attributes\Schema;
use Pimcore\Bundle\StudioBackendBundle\Document\Event\PreResponse\DocumentTypeSchemasEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final readonly class CustomSchemaSubscriber implements EventSubscriberInterface
{
    public function __construct(
    ) {

    }

    public static function getSubscribedEvents(): array
    {
        return [
            DocumentTypeSchemasEvent::EVENT_NAME  => 'addCustomDocumentSchema',
        ];
    }

    public function addCustomDocumentSchema(DocumentTypeSchemasEvent $event): void
    {
        $originalDocuments = $event->getDocumentTypeSchemas();
        // Add a custom document schema by using your custom schema title
        // #/components/schemas/{YourCustomSchemaTitle}
        $originalDocuments[] = new Schema(ref: '#/components/schemas/CustomType');

        $event->setDocumentTypeSchemas($originalDocuments);
    }
}
```

### Adding custom document type hydrator
TBA