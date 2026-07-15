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

namespace Pimcore\Bundle\StudioBackendBundle\Asset\Controller\FolderPreviewSetting;

use OpenApi\Attributes\JsonContent;
use OpenApi\Attributes\Put;
use OpenApi\Attributes\RequestBody;
use Pimcore\Bundle\StudioBackendBundle\Asset\Schema\FolderPreviewSetting;
use Pimcore\Bundle\StudioBackendBundle\Asset\Service\FolderPreviewSettingServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Parameter\Path\IntParameter;
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
    private const string ROUTE = '/assets/{folderId}/preview-setting';

    public function __construct(
        SerializerInterface $serializer,
        private readonly FolderPreviewSettingServiceInterface $folderPreviewSettingService,
    ) {
        parent::__construct($serializer);
    }

    #[Route(
        path: self::ROUTE,
        name: 'pimcore_studio_api_update_asset_folder_preview_setting',
        requirements: ['folderId' => '\d+'],
        methods: ['PUT']
    )]
    #[IsGranted(UserPermissions::ASSETS->value)]
    #[Put(
        path: self::PREFIX . self::ROUTE,
        operationId: 'asset_update_folder_preview_setting',
        description: 'asset_update_folder_preview_setting_description',
        summary: 'asset_update_folder_preview_setting_summary',
        tags: [Tags::Assets->name],
        requestBody: new RequestBody(
            required: true,
            content: new JsonContent(ref: FolderPreviewSetting::class)
        )
    )]
    #[IntParameter(name: 'folderId', example: 1, description: 'Id of the asset folder')]
    #[SuccessResponse(
        description: 'asset_update_folder_preview_setting_success_response',
        content: new JsonContent(ref: FolderPreviewSetting::class)
    )]
    #[DefaultResponses([
        HttpResponseCodes::UNAUTHORIZED,
    ])]
    public function updateFolderPreviewSetting(
        int $folderId,
        #[MapRequestPayload] FolderPreviewSetting $body
    ): JsonResponse {
        $this->folderPreviewSettingService->saveImageSize($folderId, $body->getImageSize());

        return $this->jsonResponse($this->folderPreviewSettingService->getImageSize($folderId));
    }
}
