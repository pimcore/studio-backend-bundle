# Extending Endpoints

Endpoints can be added at any given point. In order to show up in the OpenApi documentation you need to add the according OpenApi attributes.
This secures that the endpoint is documented and can be used by other developers. We are using the OpenApi standards for the documentation.

## How to add a custom endpoint

To add a custom endpoint to the Pimcore Studio Backend you need to implement it like the following:

- Extend from AbstractApiController
  - The AbstractApiController includes the base `API_PATH` and a standard way to serialize your response to json.
  - Keep in mind that for serialization the symfony serializer is used and the response must be representable in json if you want to use the standard function.
- Add the route attribute like in a standard symfony way
- For security checks you can use the IsGranted attribute
  - This will invoke the according symfony voter and checks the current logged-in user for the given permission
  - If the user does not have the permission a 403 response is returned
- Add the OpenApi method according to your route
  - In order that your endpoint shows up in the OpenApi documentation you need to add the OpenApi method
  - If your route specifies that it is a `POST` route use the according OpenApi method `OpenApi\Attributes\Post`
- Add the necessary OpenApi attributes
  - Add at least one response, you can check out the existing responses here: -- TODO ADD PAGE WITH RESPONSES --
  - You can also add some DefaultResponses
  - Add query params or request payload if necessary. You can check out the existing parameters here: -- TODO ADD PAGE WITH PARAMETERS --
  - If you need specific parameters, payloads or responses you can extend OpenApi Schemas, Properties, Responses, etc.
  - How to extend OpenApi Schemas, Properties, Responses, etc. is described [here](02_OpenApi.md)
- Implement your endpoint logic in the standard symfony way
  - We try to leverage symfony functionality as much as possible for parameters with `#[MapQueryString]` or `#[MapRequestPayload]` attributes

### Example

```php
<?php
declare(strict_types=1);
namespace Pimcore\Bundle\StudioBackendBundle\Note\Controller;

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

/**
 * @internal
 */
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