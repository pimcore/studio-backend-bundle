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
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Schema\KeyLayout;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\KeyGroupLayoutServiceInterface;
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
final class GetLayoutByKeyController extends AbstractApiController
{
    use PaginatedResponseTrait;

    public function __construct(
        SerializerInterface $serializer,
        private readonly KeyGroupLayoutServiceInterface $keyGroupLayoutService,
    ) {
        parent::__construct($serializer);
    }

    /**
     * @throws Exception
     * @throws NotFoundException
     */
    #[Route(
        path: '/classification-store/layout-by-key/{keyId}/{groupId}',
        name: 'pimcore_studio_api_classification_store_get_layout_by_key',
        methods: ['GET']
    )]
    #[IsGranted(UserPermissions::DATA_OBJECTS->value)]
    #[Get(
        path: self::PREFIX . '/classification-store/layout-by-key/{keyId}/{groupId}',
        operationId: 'classification_store_get_layout_by_key',
        description: 'classification_store_get_layout_by_key_description',
        summary: 'classification_store_get_layout_by_key_summary',
        tags: [Tags::ClassificationStore->value]
    )]
    #[SuccessResponse(
        description: 'classification_store_get_layout_by_key_response',
        content: new JsonContent(ref: KeyLayout::class, type: 'object')
    )]
    #[QueryIdParameter(
        description: 'object ID',
        namePrefix: 'object',
        required: false
    )]
    #[PathIdParameter(
        type: 'Key ID',
        name: 'keyId',
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
    public function getLayoutByKey(
        #[MapQueryString] LayoutParameter $layoutParameter,
        int $keyId,
        int $groupId
    ): JsonResponse {
        return $this->jsonResponse(
            $this->keyGroupLayoutService->getKeyLayout($layoutParameter, $keyId, $groupId)
        );
    }
}
