---
title: Extending OpenAPI
description: Create reusable OpenAPI attribute classes for custom endpoints.
---

# Extending OpenAPI

The Studio Backend uses [zircote/swagger-php](https://zircote.github.io/swagger-php/) to generate
OpenAPI documentation from PHP attributes. You can use the library's standard attributes directly,
or create custom attribute classes that encapsulate common patterns and reduce duplication in
controller annotations.

## Creating Custom Attributes

Extend an existing OpenAPI attribute (e.g. `RequestBody`, `Schema`) and set defaults in the
constructor. Use the custom attribute on your controller method (e.g. `#[CreateNoteRequestBody]`).

### Example: Custom Request Body Attribute
```php
<?php
declare(strict_types=1);

namespace App\Note\Attribute\Request;

use Attribute;
use OpenApi\Attributes\JsonContent;
use OpenApi\Attributes\RequestBody;
use Pimcore\Bundle\StudioBackendBundle\Note\Schema\CreateNote;

#[Attribute(Attribute::TARGET_METHOD)]
final class CreateNoteRequestBody extends RequestBody
{
    public function __construct()
    {
        parent::__construct(
            required: true,
            content: new JsonContent(ref: CreateNote::class)
        );
    }
}

```

## Creating Custom Schemas

Define schema classes with `#[Schema]` and `#[Property]` attributes. Each schema appears in the
"Schemas" section of the Swagger UI. Schema class names must be unique across the application.

### Example: Custom Schema
```php
<?php
declare(strict_types=1);

namespace App\Note\Schema;

use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;


#[Schema(
    title: 'CreateNote',
    required: [
        'title',
        'description',
        'type',
    ],
    type: 'object'
)]
final readonly class CreateNote
{
    public function __construct(
        #[Property(description: 'title', type: 'string', example: 'Title of note')]
        private string $title,
        #[Property(description: 'description', type: 'string', example: 'Description of note')]
        private string $description,
        #[Property(description: 'type', type: 'string', example: 'Type of note')]
        private string $type
    ) {
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getType(): string
    {
        return $this->type;
    }
}
```