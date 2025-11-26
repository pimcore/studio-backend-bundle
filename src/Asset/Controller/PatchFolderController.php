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

namespace Pimcore\Bundle\StudioBackendBundle\Asset\Controller;

use OpenApi\Attributes\Patch;
use Pimcore\Bundle\StudioBackendBundle\Asset\Attribute\Request\PatchAssetFolderRequestBody;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\UserNotFoundException;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\PatchFolderParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Parameter\Path\IdParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\Content\IdJson;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\CreatedResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\Patcher\Service\PatchServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementTypes;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\HttpResponseCodes;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\UserPermissions;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @internal
 */
final class PatchFolderController extends AbstractApiController
{
    private const string ROUTE = '/assets/folder/{id}';

    public function __construct(
        SerializerInterface $serializer,
        private readonly PatchServiceInterface $patchService,
        private readonly SecurityServiceInterface $securityService
    ) {
        parent::__construct($serializer);
    }

    /**
     * @throws UserNotFoundException
     */
    #[Route(self::ROUTE, name: 'pimcore_studio_api_patch_asset_folder', methods: ['PATCH'])]
    #[IsGranted(UserPermissions::ASSETS->value)]
    #[Patch(
        path: self::PREFIX . self::ROUTE,
        operationId: 'asset_patch_folder_by_id',
        description: 'asset_patch_folder_by_id_description',
        summary: 'asset_patch_folder_by_id_summary',
        tags: [Tags::Assets->name]
    )]
    #[IdParameter(type: ElementTypes::TYPE_FOLDER, name: 'id')]
    #[PatchAssetFolderRequestBody]
    #[CreatedResponse(
        description: 'asset_patch_by_id_created_response',
        content: new IdJson('ID of created jobRun', 'jobRunId')
    )]
    #[DefaultResponses([
        HttpResponseCodes::NOT_FOUND,
        HttpResponseCodes::UNAUTHORIZED,
    ])]
    public function assetPatchFolderById (
        int $id,
        #[MapRequestPayload] PatchFolderParameter $patchFolderParameter
    ): Response
    {
        $jobRunId = $this->patchService->patchFolder(
            $id,
            ElementTypes::TYPE_ASSET,
            $patchFolderParameter,
            $this->securityService->getCurrentUser()
        );

        return $this->jsonResponse(['jobRunId' => $jobRunId], HttpResponseCodes::CREATED->value);
    }
}
