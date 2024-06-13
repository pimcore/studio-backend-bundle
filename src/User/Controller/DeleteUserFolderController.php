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

namespace Pimcore\Bundle\StudioBackendBundle\User\Controller;

use OpenApi\Attributes\Delete;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\DatabaseException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attributes\Parameters\Path\IdParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attributes\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attributes\Response\SuccessResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\User\Service\UserFolderServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constants\HttpResponseCodes;
use Pimcore\Bundle\StudioBackendBundle\Util\Constants\UserPermissions;
use Pimcore\Bundle\StudioBackendBundle\Util\Traits\PaginatedResponseTrait;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @internal
 */
final class DeleteUserFolderController extends AbstractApiController
{
    use PaginatedResponseTrait;
    public function __construct(
        SerializerInterface $serializer,
        private readonly UserFolderServiceInterface $userFolderService
    ) {
        parent::__construct($serializer);
    }


    /**
     * @throws ForbiddenException|NotFoundException|DatabaseException
     */
    #[Route('/user/folder/{id}', name: 'pimcore_studio_api_user_folder_delete', methods: ['DELETE'])]
    #[IsGranted(UserPermissions::PIMCORE_ADMIN->value)]
    #[Delete(
        path: self::API_PATH . '/user/folder/{id}',
        operationId: 'deleteUserFolder',
        summary: 'Delete a specific user folder with all users in this folder.',
        tags: [Tags::User->value]
    )]
    #[SuccessResponse]
    #[IdParameter(type: 'user-folder')]
    #[DefaultResponses([
        HttpResponseCodes::NOT_FOUND
    ])]
    public function deleteUserFolder(int $id): Response
    {
        $this->userFolderService->deleteUserFolderById($id);
        return new Response();
    }
}
