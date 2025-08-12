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

use OpenApi\Attributes\Get;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\DatabaseException;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Parameter\Query\TextFieldParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Property\GenericCollection;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\Content\CollectionJson;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\SuccessResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\Role\MappedParameter\RolePermissionParameter;
use Pimcore\Bundle\StudioBackendBundle\Role\Schema\SimpleRole;
use Pimcore\Bundle\StudioBackendBundle\Role\Service\RoleServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\HttpResponseCodes;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\UserPermissions;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @internal
 */
final class GetRolesWithPermissionController extends AbstractApiController
{
    private const string ROUTE = '/roles/with-permission';

    public function __construct(
        SerializerInterface $serializer,
        private readonly RoleServiceInterface $roleService,
    ) {
        parent::__construct($serializer);
    }

    /**
     * @throws DatabaseException
     */
    #[Route(self::ROUTE, name: 'pimcore_studio_api_roles_with_permission_list', methods: ['GET'])]
    #[IsGranted(UserPermissions::SHARE_CONFIGURATIONS->value)]
    #[Get(
        path: self::PREFIX . self::ROUTE,
        operationId: 'role_list_with_permission',
        description: 'role_list_with_permission_description',
        summary: 'role_list_with_permission_summary',
        tags: [Tags::Role->value]
    )]
    #[TextFieldParameter(
        name: 'permission',
        description: 'List roles with this permission',
        required: true,
        example: UserPermissions::ASSETS->value
    )]
    #[SuccessResponse(
        description: 'role_list_with_permission_success_response',
        content: new CollectionJson(new GenericCollection(SimpleRole::class))
    )]
    #[DefaultResponses([
        HttpResponseCodes::INTERNAL_SERVER_ERROR,
        HttpResponseCodes::UNAUTHORIZED,
    ])]
    public function listRolesWithPermissions(#[MapQueryString] RolePermissionParameter $parameters): JsonResponse
    {
        return $this->jsonResponse($this->roleService->getRolesWithPermission($parameters));
    }
}
