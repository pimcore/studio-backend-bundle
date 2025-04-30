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

namespace Pimcore\Bundle\StudioBackendBundle\Tag\Controller;

use OpenApi\Attributes\JsonContent;
use OpenApi\Attributes\Put;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Parameter\Path\IdParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\SuccessResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\Tag\Attribute\Request\UpdateTagRequestBody;
use Pimcore\Bundle\StudioBackendBundle\Tag\MappedParameter\UpdateTagParameters;
use Pimcore\Bundle\StudioBackendBundle\Tag\Schema\Tag;
use Pimcore\Bundle\StudioBackendBundle\Tag\Service\TagServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\HttpResponseCodes;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\UserPermissions;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @internal
 */
final class UpdateController extends AbstractApiController
{
    public function __construct(
        SerializerInterface $serializer,
        private readonly TagServiceInterface $tagService,
    ) {
        parent::__construct($serializer);
    }

    #[Route('/tags/{id}', name: 'pimcore_studio_api_update_tag', methods: ['PUT'])]
    #[Put(
        path: self::PREFIX . '/tags/{id}',
        operationId: 'tag_update_by_id',
        description: 'tag_update_by_id_description',
        summary: 'tag_update_by_id_summary',
        tags: [Tags::Tags->name]
    )]
    #[IsGranted(UserPermissions::TAGS_CONFIGURATION->value)]
    #[IdParameter(type: 'tag')]
    #[UpdateTagRequestBody]
    #[SuccessResponse(
        description: 'tag_update_by_id_success_response',
        content: new JsonContent(ref: Tag::class, type: 'object')
    )]
    #[DefaultResponses([
        HttpResponseCodes::UNAUTHORIZED,
        HttpResponseCodes::NOT_FOUND,
    ])]
    public function updateTag(int $id, #[MapRequestPayload] UpdateTagParameters $parameters): JsonResponse
    {
        $this->tagService->updateTag($id, $parameters);

        return $this->jsonResponse($this->tagService->getTag($id));
    }
}
