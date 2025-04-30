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

namespace Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Controller;

use Exception;
use OpenApi\Attributes\Get;
use OpenApi\Attributes\JsonContent;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\MappedParameter\LayoutParameter;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Schema\GroupLayout;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\GroupServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Parameter\Path\IdParameter as PathIdParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Parameter\Query\IdParameter as QueryIdParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Parameter\Query\TextFieldParameter;
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
final class GetLayoutByGroupController extends AbstractApiController
{
    use PaginatedResponseTrait;

    public function __construct(
        SerializerInterface $serializer,
        private readonly GroupServiceInterface $groupService,
    ) {
        parent::__construct($serializer);
    }

    /**
     * @throws Exception
     * @throws NotFoundException
     */
    #[Route(
        path: '/classification-store/layout-by-group/{groupId}',
        name: 'pimcore_studio_api_classification_store_get_layout_by_group',
        methods: ['GET']
    )]
    #[IsGranted(UserPermissions::DATA_OBJECTS->value)]
    #[Get(
        path: self::PREFIX . '/classification-store/layout-by-group/{groupId}',
        operationId: 'classification_store_get_layout_by_group',
        description: 'classification_store_get_layout_by_group_description',
        summary: 'classification_store_get_layout_by_group_summary',
        tags: [Tags::ClassificationStore->value]
    )]
    #[SuccessResponse(
        description: 'classification_store_get_layout_by_group_response',
        content: new JsonContent(ref: GroupLayout::class, type: 'object')
    )]
    #[QueryIdParameter(
        description: 'object ID',
        namePrefix: 'object',
        required: true
    )]
    #[PathIdParameter(
        type: 'Group ID',
        name: 'groupId',
    )]
    #[TextFieldParameter(
        name: 'fieldName',
        description: 'Field Name',
        required: true,
        example: 'technicalAttributes'
    )]
    #[DefaultResponses([
        HttpResponseCodes::FORBIDDEN,
        HttpResponseCodes::UNAUTHORIZED,
        HttpResponseCodes::NOT_FOUND,
    ])]
    public function getLayoutByGroup(
        #[MapQueryString] LayoutParameter $layoutParameter,
        int $groupId,
    ): JsonResponse {
        return $this->jsonResponse(
            $this->groupService->getLayoutDefinition($groupId, $layoutParameter)
        );
    }
}
