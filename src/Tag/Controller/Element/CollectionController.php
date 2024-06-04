<?php
declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Bundle\StudioBackendBundle\Tag\Controller\Element;

use OpenApi\Attributes\Get;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attributes\Parameters\Path\ElementTypeParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attributes\Parameters\Path\IdParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attributes\Response\Content\CollectionJson;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attributes\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attributes\Response\SuccessResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\Tag\Attributes\Response\Property\TagCollection;
use Pimcore\Bundle\StudioBackendBundle\Tag\MappedParameter\ElementParameters;
use Pimcore\Bundle\StudioBackendBundle\Tag\MappedParameter\TagsParameters;
use Pimcore\Bundle\StudioBackendBundle\Tag\Service\TagServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constants\HttpResponseCodes;
use Pimcore\Bundle\StudioBackendBundle\Util\Traits\PaginatedResponseTrait;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;
use function count;

/**
 * @internal
 */
final class CollectionController extends AbstractApiController
{
    use PaginatedResponseTrait;

    public function __construct(
        SerializerInterface $serializer,
        private readonly TagServiceInterface $tagService
    )
    {
        parent::__construct($serializer);
    }

    #[Route('/tags/{elementType}/{id}', name: 'pimcore_studio_api_get_element_tags', methods: ['GET'])]
    //#[IsGranted(UserPermissions::TAGS_SEARCH->value)]
    #[Get(
        path: self::API_PATH . '/tags/{elementType}/{id}',
        operationId: 'getTagsForElementByTypeAndId',
        summary: 'Get tags for an element',
        security: self::SECURITY_SCHEME,
        tags: [Tags::TagsForElement->value]
    )]
    #[ElementTypeParameter]
    #[IdParameter(type: 'element')]
    #[SuccessResponse(
        description: 'List of tags',
        content: new CollectionJson(new TagCollection())
    )]
    #[DefaultResponses([
        HttpResponseCodes::UNAUTHORIZED
    ])]
    public function getTags(
        string $elementType,
        int $id,
        #[MapQueryString] TagsParameters $parameters = new TagsParameters()
    ): JsonResponse
    {
        $collection = $this->tagService->getTagsForElement(new ElementParameters($elementType, $id));

        return $this->getPaginatedCollection(
            $this->serializer,
            $collection,
            count($collection)
        );
    }
}
