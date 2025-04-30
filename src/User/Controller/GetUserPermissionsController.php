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

namespace Pimcore\Bundle\StudioBackendBundle\User\Controller;

use OpenApi\Attributes\Get;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Property\GenericCollection;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\Content\CollectionJson;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\SuccessResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\User\Schema\UserPermission;
use Pimcore\Bundle\StudioBackendBundle\User\Service\UserPermissionServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\UserPermissions;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\PaginatedResponseTrait;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @internal
 */
final class GetUserPermissionsController extends AbstractApiController
{
    use PaginatedResponseTrait;

    public function __construct(
        SerializerInterface $serializer,
        private readonly UserPermissionServiceInterface $userPermissionsService
    ) {
        parent::__construct($serializer);
    }

    #[Route('/user/available-permissions', name: 'pimcore_studio_api_user_available_permissions', methods: ['GET'])]
    #[IsGranted(UserPermissions::USER_MANAGEMENT->value)]
    #[Get(
        path: self::PREFIX . '/user/available-permissions',
        operationId: 'user_get_available_permissions',
        summary: 'user_get_available_permissions_summary',
        tags: [Tags::User->value]
    )]
    #[SuccessResponse(
        description: 'user_get_available_permissions_success_response',
        content: new CollectionJson(new GenericCollection(UserPermission::class))
    )]
    #[DefaultResponses]
    public function getAvailablePermissions(): JsonResponse
    {
        $permissions = $this->userPermissionsService->getAvailablePermissions();

        return $this->getPaginatedCollection(
            $this->serializer,
            $permissions->getItems(),
            $permissions->getTotalItems()
        );
    }
}
