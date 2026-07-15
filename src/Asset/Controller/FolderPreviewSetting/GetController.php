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

use OpenApi\Attributes\Get;
use OpenApi\Attributes\JsonContent;
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
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @internal
 */
final class GetController extends AbstractApiController
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
        name: 'pimcore_studio_api_get_asset_folder_preview_setting',
        requirements: ['folderId' => '\d+'],
        methods: ['GET']
    )]
    #[IsGranted(UserPermissions::ASSETS->value)]
    #[Get(
        path: self::PREFIX . self::ROUTE,
        operationId: 'asset_get_folder_preview_setting',
        description: 'asset_get_folder_preview_setting_description',
        summary: 'asset_get_folder_preview_setting_summary',
        tags: [Tags::Assets->name]
    )]
    #[IntParameter(name: 'folderId', example: 1, description: 'Id of the asset folder')]
    #[SuccessResponse(
        description: 'asset_get_folder_preview_setting_success_response',
        content: new JsonContent(ref: FolderPreviewSetting::class)
    )]
    #[DefaultResponses([
        HttpResponseCodes::UNAUTHORIZED,
    ])]
    public function getFolderPreviewSetting(int $folderId): JsonResponse
    {
        return $this->jsonResponse($this->folderPreviewSettingService->getImageSize($folderId));
    }
}
