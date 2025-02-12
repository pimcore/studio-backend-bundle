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

namespace Pimcore\Bundle\StudioBackendBundle\DataObject\Controller;

use OpenApi\Attributes\Patch;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Attribute\Request\PatchDataObjectFolderRequestBody;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\UserNotFoundException;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\PatchFolderParameter;
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
    #[Route('/data-objects/folder', name: 'pimcore_studio_api_patch_data_object_folder', methods: ['PATCH'])]
    #[IsGranted(UserPermissions::DATA_OBJECTS->value)]
    #[Patch(
        path: self::PREFIX . '/data-objects/folder',
        operationId: 'data_object_patch_folder_by_id',
        description: 'data_object_patch_folder_by_id_description',
        summary: 'data_object_patch_folder_by_id_summary',
        tags: [Tags::DataObjects->value]
    )]
    #[PatchDataObjectFolderRequestBody]
    #[CreatedResponse(
        description: 'data_object_patch_by_id_created_response',
        content: new IdJson('ID of created jobRun', 'jobRunId')
    )]
    #[DefaultResponses([
        HttpResponseCodes::NOT_FOUND,
        HttpResponseCodes::UNAUTHORIZED,
    ])]
    public function dataObjectsPatchFolderById(
        #[MapRequestPayload] PatchFolderParameter $patchFolderParameter
    ): Response {

        $jobRunId = $this->patchService->patchFolder(
            ElementTypes::TYPE_OBJECT,
            $patchFolderParameter,
            $this->securityService->getCurrentUser()
        );

        return $this->jsonResponse(['jobRunId' => $jobRunId], HttpResponseCodes::CREATED->value);
    }
}
