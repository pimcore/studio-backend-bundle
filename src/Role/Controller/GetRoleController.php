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

namespace Pimcore\Bundle\StudioBackendBundle\Role\Controller;

use OpenApi\Attributes\Get;
use OpenApi\Attributes\JsonContent;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\DatabaseException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attributes\Parameters\Path\IdParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attributes\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attributes\Response\SuccessResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\Role\Schema\DetailedRole as DetailedRoleSchema;
use Pimcore\Bundle\StudioBackendBundle\Role\Service\RoleServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constants\HttpResponseCodes;
use Pimcore\Bundle\StudioBackendBundle\Util\Constants\UserPermissions;
use Pimcore\Bundle\StudioBackendBundle\Util\Traits\PaginatedResponseTrait;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @internal
 */
final class GetRoleController extends AbstractApiController
{
    use PaginatedResponseTrait;

    public function __construct(
        SerializerInterface $serializer,
        private readonly RoleServiceInterface $roleService
    ) {
        parent::__construct($serializer);
    }

    /**
     * @throws NotFoundException|DatabaseException
     */
    #[Route('/role/{id}', name: 'pimcore_studio_api_role_get', methods: ['GET'])]
    #[IsGranted(UserPermissions::USER_MANAGEMENT->value)]
    #[Get(
        path: self::API_PATH . '/role/{id}',
        operationId: 'role_get_by_id',
        summary: 'Get role by id.',
        tags: [Tags::Role->value]
    )]
    #[IdParameter(type: 'role')]
    #[SuccessResponse(
        description: 'Role data.',
        content: new JsonContent(ref: DetailedRoleSchema::class)
    )]
    #[DefaultResponses([
        HttpResponseCodes::NOT_FOUND,
    ])]
    public function getRoleById(int $id): JsonResponse
    {
        return $this->jsonResponse(
            $this->roleService->getRoleById($id)
        );
    }
}
