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

namespace Pimcore\Bundle\StudioBackendBundle\Role\Controller;

use OpenApi\Attributes\Delete;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\DatabaseException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Parameter\Path\IdParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\SuccessResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\Role\Service\FolderServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\HttpResponseCodes;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\UserPermissions;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @internal
 */
final class DeleteFolderController extends AbstractApiController
{
    public function __construct(
        SerializerInterface $serializer,
        private readonly FolderServiceInterface $folderService
    ) {
        parent::__construct($serializer);
    }

    /**
     * @throws NotFoundException|DatabaseException
     */
    #[Route('/role/folder/{id}', name: 'pimcore_studio_api_role_folder_delete', methods: ['DELETE'])]
    #[IsGranted(UserPermissions::USER_MANAGEMENT->value)]
    #[Delete(
        path: self::PREFIX . '/role/folder/{id}',
        operationId: 'role_folder_delete_by_id',
        summary: 'role_folder_delete_by_id_summary',
        tags: [Tags::Role->value]
    )]
    #[SuccessResponse]
    #[IdParameter(type: 'folder')]
    #[DefaultResponses([
        HttpResponseCodes::NOT_FOUND,
        HttpResponseCodes::INTERNAL_SERVER_ERROR,
    ])]
    public function deleteRoleFolder(int $id): Response
    {
        $this->folderService->deleteFolder($id);

        return new Response();
    }
}
