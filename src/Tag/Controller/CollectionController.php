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
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attributes\Content\ItemsJson;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attributes\Parameters\Query\ElementTypeParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attributes\Parameters\Query\FilterParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attributes\Parameters\Query\PageParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attributes\Parameters\Query\PageSizeParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attributes\Parameters\Query\ParentIdParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attributes\Response\Error\BadRequestResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attributes\Response\Error\MethodNotAllowedResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attributes\Response\Error\UnauthorizedResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attributes\Response\Error\UnprocessableContentResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attributes\Response\Error\UnsupportedMediaTypeResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attributes\Response\SuccessResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\Tag\MappedParameter\TagsParameters;
use Pimcore\Bundle\StudioBackendBundle\Tag\Schema\Tag;
use Pimcore\Bundle\StudioBackendBundle\Tag\Service\TagServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constants\UserPermissions;
use Pimcore\Bundle\StudioBackendBundle\Util\Traits\PaginatedResponseTrait;
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
        path: self::API_PATH . '/tags',
        operationId: 'getTags',
        summary: 'Get all tags for a parent id. You can filter by type and query',
        tags: [Tags::Tags->name]
    )]
    #[PageParameter]
    #[PageSizeParameter]
    #[ElementTypeParameter(false, null)]
    #[FilterParameter]
    #[ParentIdParameter(
        description: 'Filter tags by parent id.',
        example: null
    )]
    #[SuccessResponse(
        description: 'Tags filtered based on type and query parameters',
        content: new ItemsJson(Tag::class)
    )]
    #[BadRequestResponse]
    #[UnauthorizedResponse]
    #[MethodNotAllowedResponse]
    #[UnsupportedMediaTypeResponse]
    #[UnprocessableContentResponse]
    public function getTags(
        #[MapQueryString] TagsParameters $parameters): JsonResponse
    {
        return $this->jsonResponse(['items' => $this->tagService->listTags($parameters)]);
    }
}
