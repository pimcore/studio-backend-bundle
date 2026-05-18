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

namespace Pimcore\Bundle\StudioBackendBundle\Class\Controller\ObjectBrick\CustomLayout;

use OpenApi\Attributes\JsonContent;
use OpenApi\Attributes\Put;
use Pimcore\Bundle\StudioBackendBundle\Class\Attribute\Request\CustomLayoutUpdateRequestBody;
use Pimcore\Bundle\StudioBackendBundle\Class\MappedParameter\UpdateParameters;
use Pimcore\Bundle\StudioBackendBundle\Class\Schema\CustomLayout\CustomLayout;
use Pimcore\Bundle\StudioBackendBundle\Class\Service\CustomLayoutServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotWriteableException;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Parameter\Path\StringParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\SuccessResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
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
    private const string ROUTE = '/class/object-brick/{key}/custom-layout/{customLayoutId}';

    public function __construct(
        SerializerInterface $serializer,
        private readonly CustomLayoutServiceInterface $customLayoutService,
    ) {
        parent::__construct($serializer);
    }

    /**
     * @throws NotFoundException|NotWriteableException|InvalidArgumentException
     */
    #[Route(self::ROUTE, name: 'pimcore_studio_api_class_object_brick_custom_layout_update', methods: ['PUT'])]
    #[IsGranted(UserPermissions::OBJECT_BRICKS->value)]
    #[Put(
        path: self::PREFIX . self::ROUTE,
        operationId: 'class_object_brick_custom_layout_update',
        description: 'class_object_brick_custom_layout_update_description',
        summary: 'class_object_brick_custom_layout_update_summary',
        tags: [Tags::ClassDefinition->value],
    )]
    #[StringParameter(
        name: 'key',
        example: 'SaleInformation',
        description: 'class_object_brick_custom_layout_update_key',
        required: true
    )]
    #[StringParameter(
        name: 'customLayoutId',
        example: 'CarTodo',
        description: 'class_object_brick_custom_layout_update_layout_id',
        required: true
    )]
    #[CustomLayoutUpdateRequestBody(false)]
    #[SuccessResponse(
        description: 'class_object_brick_custom_layout_update_success_response',
        content: new JsonContent(ref: CustomLayout::class, type: 'object')
    )]
    #[DefaultResponses([
        HttpResponseCodes::NOT_FOUND,
        HttpResponseCodes::INTERNAL_SERVER_ERROR,
        HttpResponseCodes::UNAUTHORIZED,
        HttpResponseCodes::BAD_REQUEST,
    ])]
    public function updateBrickCustomLayout(
        string $key,
        string $customLayoutId,
        #[MapRequestPayload] UpdateParameters $parameters = new UpdateParameters([], []),
    ): JsonResponse {
        return $this->jsonResponse(
            $this->customLayoutService->updateBrickCustomLayout($key, $customLayoutId, $parameters)
        );
    }
}
