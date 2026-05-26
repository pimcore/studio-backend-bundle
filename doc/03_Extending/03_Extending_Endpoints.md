---
title: Extending Endpoints
description: Add custom REST API endpoints to the Studio Backend.
---

# Extending Endpoints

Add custom API endpoints by extending `AbstractApiController` and annotating with OpenAPI attributes.
All endpoints must include OpenAPI documentation so they appear in the Swagger UI.

## Implementation Steps

1. **Extend `AbstractApiController`** - provides the base `API_PATH` and JSON serialization via the Symfony serializer.

2. **Define the route** using Symfony's `#[Route]` attribute.

3. **Add security** with `#[IsGranted]` - the `UserPermissionVoter` checks the current user's permissions
   and returns a 403 response if denied.

4. **Add OpenAPI attributes** - use the matching HTTP method attribute (e.g. `#[Get]`, `#[Post]`).
   Include at least one response annotation. Use `#[DefaultResponses]` for standard error responses.
   For query parameters, use `#[MapQueryString]`; for request bodies, use `#[MapRequestPayload]`.

5. **Add custom OpenAPI schemas** if needed. See [Extending OpenAPI](./04_Extending_OpenApi.md)
   for creating reusable attribute classes.

We try to leverage symfony functionality as much as possible for parameters with `#[MapQueryString]` or 
`#[MapRequestPayload]` attributes.

### Example

```php
<?php
declare(strict_types=1);
namespace App\Note\Controller;

use OpenApi\Attributes\Get;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidFilterException;
use Pimcore\Bundle\StudioBackendBundle\Note\Attribute\Parameter\Query\NoteSortByParameter;
use Pimcore\Bundle\StudioBackendBundle\Note\MappedParameter\NoteElementParameters;
use Pimcore\Bundle\StudioBackendBundle\Note\MappedParameter\NoteParameters;
use Pimcore\Bundle\StudioBackendBundle\Note\Schema\Note;
use Pimcore\Bundle\StudioBackendBundle\Note\Service\NoteServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Parameter\Query\FieldFilterParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Parameter\Query\FilterParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Parameter\Query\PageParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Parameter\Query\PageSizeParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Parameter\Query\SortOrderParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Property\GenericCollection;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\Content\CollectionJson;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\SuccessResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\HttpResponseCodes;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\UserPermissions;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\PaginatedResponseTrait;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;


final class CollectionController extends AbstractApiController
{
    use PaginatedResponseTrait;

    public function __construct(
        SerializerInterface $serializer,
        private readonly NoteServiceInterface $noteService
    ) {
        parent::__construct($serializer);
    }

    /**
     * @throws InvalidFilterException
     */
    #[Route('/notes', name: 'pimcore_studio_api_get_notes', methods: ['GET'])]
    #[IsGranted(UserPermissions::NOTES_EVENTS->value)]
    #[Get(
        path: self::PREFIX . '/notes',
        operationId: 'note_get_collection',
        description: 'note_get_collection_description',
        summary: 'note_get_collection_summary',
        tags: [Tags::Notes->name]
    )]
    #[PageParameter]
    #[PageSizeParameter(50)]
    #[NoteSortByParameter]
    #[SortOrderParameter]
    #[FilterParameter('notes')]
    #[FieldFilterParameter]
    #[SuccessResponse(
        description: 'note_get_collection_success_response',
        content: new CollectionJson(new GenericCollection(Note::class))
    )]
    #[DefaultResponses([
        HttpResponseCodes::UNAUTHORIZED,
    ])]
    public function getNotes(
        #[MapQueryString] NoteParameters $parameters = new NoteParameters()
    ): JsonResponse {
        $collection = $this->noteService->listNotes(new NoteElementParameters(), $parameters);

        return $this->getPaginatedCollection(
            $this->serializer,
            $collection->getItems(),
            $collection->getTotalItems()
        );
    }
}
```

### Available classes for simplification

- [Collection](https://github.com/pimcore/studio-backend-bundle/blob/2026.x/src/Response/Collection.php) - Predefined response object for collections with the total item count and items.
- [HttpResponseCodes](https://github.com/pimcore/studio-backend-bundle/blob/2026.x/src/Util/Constant/HttpResponseCodes.php) - Predefined HTTP response codes.