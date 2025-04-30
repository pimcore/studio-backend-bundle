<?php
declare(strict_types=1);

/**
 * This source file is available under the terms of the
 * Pimcore Open Core License (POCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (https://www.pimcore.com)
 *  @license    Pimcore Open Core License (POCL)
 */

namespace Pimcore\Bundle\StudioBackendBundle\Tag\Controller\Element;

use OpenApi\Attributes\Delete;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ElementSavingFailedException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Parameter\Path\ElementTypeParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Parameter\Path\IdParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\Tag\MappedParameter\ElementParameters;
use Pimcore\Bundle\StudioBackendBundle\Tag\Service\TagServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\HttpResponseCodes;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\UserPermissions;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @internal
 */
final class UnassignController extends AbstractApiController
{
    public function __construct(
        SerializerInterface $serializer,
        private readonly TagServiceInterface $tagService,
    ) {
        parent::__construct($serializer);
    }

    /**
     * @throws ElementSavingFailedException|NotFoundException
     */
    #[Route('/tags/{elementType}/{id}/{tagId}', name: 'pimcore_studio_api_unassign_element_tag', methods: ['DELETE'])]
    #[IsGranted(UserPermissions::TAGS_ASSIGNMENT->value)]
    #[Delete(
        path: self::PREFIX . '/tags/{elementType}/{id}/{tagId}',
        operationId: 'tag_unassign_from_element',
        description: 'tag_unassign_from_element_description',
        summary: 'tag_unassign_from_element_summary',
        tags: [Tags::TagsForElement->value]
    )]
    #[ElementTypeParameter]
    #[IdParameter]
    #[IdParameter(type: 'tag', name: 'tagId')]
    #[DefaultResponses([
        HttpResponseCodes::UNAUTHORIZED,
    ])]
    public function unassignTag(
        string $elementType,
        int $id,
        int $tagId
    ): JsonResponse {
        $this->tagService->unassignTagFromElement(new ElementParameters($elementType, $id), $tagId);

        return $this->jsonResponse(['id' => $id]);
    }
}
