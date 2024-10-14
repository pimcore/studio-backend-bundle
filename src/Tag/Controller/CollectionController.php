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

namespace Pimcore\Bundle\StudioBackendBundle\Tag\Controller;

use OpenApi\Attributes\Get;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidQueryTypeException;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Content\ItemsJson;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Parameter\Query\ElementTypeParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Parameter\Query\FilterParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Parameter\Query\PageParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Parameter\Query\PageSizeParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Parameter\Query\ParentIdParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\SuccessResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\Tag\MappedParameter\TagsParameters;
use Pimcore\Bundle\StudioBackendBundle\Tag\Schema\Tag;
use Pimcore\Bundle\StudioBackendBundle\Tag\Service\TagServiceInterface;
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
        private readonly TagServiceInterface $tagService,
    ) {
        parent::__construct($serializer);
    }

    /**
     * @throws InvalidQueryTypeException
     */
    #[Route('/tags', name: 'pimcore_studio_api_tags', methods: ['GET'])]
    #[IsGranted(UserPermissions::TAGS_SEARCH->value)]
    #[Get(
        path: self::PREFIX . '/tags',
        operationId: 'tag_get_collection',
        description: 'tag_get_collection_description',
        summary: 'tag_get_collection_summary',
        tags: [Tags::Tags->name]
    )]
    #[PageParameter]
    #[PageSizeParameter]
    #[ElementTypeParameter(false, null)]
    #[FilterParameter]
    #[ParentIdParameter(
        description: 'Filter tags by parent id.',
        minimum: 0,
        example: null
    )]
    #[SuccessResponse(
        description: 'tag_get_collection_success_response',
        content: new ItemsJson(Tag::class)
    )]
    #[DefaultResponses([
        HttpResponseCodes::UNAUTHORIZED,
    ])]
    public function getTags(
        #[MapQueryString] TagsParameters $parameters): JsonResponse
    {
        return $this->jsonResponse(['items' => $this->tagService->listTags($parameters)]);
    }
}
