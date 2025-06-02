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

namespace Pimcore\Bundle\StudioBackendBundle\DataObject\Controller\Grid;

use Exception;
use OpenApi\Attributes\JsonContent;
use OpenApi\Attributes\Post;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Grid\Attribute\Request\AdvancedColumnPreviewRequestBody;
use Pimcore\Bundle\StudioBackendBundle\Grid\MappedParameter\AdvancedColumnPreviewParameter;
use Pimcore\Bundle\StudioBackendBundle\Grid\Schema\ColumnData;
use Pimcore\Bundle\StudioBackendBundle\Grid\Service\GridServiceInterface;
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
final class AdvancedColumnPreviewController extends AbstractApiController
{
    public function __construct(
        SerializerInterface $serializer,
        private readonly GridServiceInterface $gridService
    ) {
        parent::__construct($serializer);
    }

    /**
     * @throws InvalidArgumentException
     * @throws Exception
     * @throws NotFoundException
     */
    #[Route('/data-objects/grid/preview', name: 'pimcore_studio_api_get_data_object_grid_preview', methods: ['POST'])]
    #[IsGranted(UserPermissions::DATA_OBJECTS->value)]
    #[Post(
        path: self::PREFIX . '/data-objects/grid/preview',
        operationId: 'data_object_get_grid_preview',
        description: 'data_object_get_grid_preview_description',
        summary: 'data_object_get_grid_preview_summary',
        tags: [Tags::DataObjectsGrid->value]
    )]
    #[AdvancedColumnPreviewRequestBody]
    #[SuccessResponse(
        description: 'data_object_get_grid_preview_success_response',
        content: new JsonContent(
            ref: ColumnData::class,
            type: 'object'
        )
    )]
    #[DefaultResponses([
        HttpResponseCodes::UNAUTHORIZED,
        HttpResponseCodes::NOT_FOUND,
        HttpResponseCodes::BAD_REQUEST,
    ])]
    public function getPreviewOfAdvancedColumn(
        #[MapRequestPayload] AdvancedColumnPreviewParameter $parameter,
    ): JsonResponse
    {
        return $this->jsonResponse($this->gridService->getPreviewOfAdvancedColumn($parameter));
    }
}
