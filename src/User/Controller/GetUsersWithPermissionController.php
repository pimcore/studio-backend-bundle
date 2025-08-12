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
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\DatabaseException;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Parameter\Query\BoolParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Parameter\Query\TextFieldParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Property\GenericCollection;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\Content\CollectionJson;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\SuccessResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\User\MappedParameter\UserWithPermissionParameter;
use Pimcore\Bundle\StudioBackendBundle\User\Schema\SimpleUser;
use Pimcore\Bundle\StudioBackendBundle\User\Service\UserServiceInterface;
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
final class GetUsersWithPermissionController extends AbstractApiController
{
    private const string ROUTE = '/users/with-permission';

    public function __construct(
        SerializerInterface $serializer,
        private readonly UserServiceInterface $userService,
    ) {
        parent::__construct($serializer);
    }

    /**
     * @throws DatabaseException
     */
    #[Route(self::ROUTE, name: 'pimcore_studio_api_users_with_permission_list', methods: ['GET'])]
    #[IsGranted(UserPermissions::SHARE_CONFIGURATIONS->value)]
    #[Get(
        path: self::PREFIX . self::ROUTE,
        operationId: 'user_list_with_permission',
        description: 'user_list_with_permission_description',
        summary: 'user_list_with_permission_summary',
        tags: [Tags::User->value]
    )]
    #[TextFieldParameter(
        name: 'permission',
        description: 'List users with this permission',
        required: true,
        example: UserPermissions::ASSETS->value
    )]
    #[BoolParameter(
        name: 'includeCurrentUser',
        description: 'Include current user in the list',
        required: false,
    )]
    #[SuccessResponse(
        description: ' user_list_with_permission_success_response',
        content: new CollectionJson(new GenericCollection(SimpleUser::class))
    )]
    #[DefaultResponses([
        HttpResponseCodes::INTERNAL_SERVER_ERROR,
        HttpResponseCodes::UNAUTHORIZED,
    ])]
    public function listUsersWithPermissions(#[MapQueryString] UserWithPermissionParameter $parameters): JsonResponse
    {
        return $this->jsonResponse($this->userService->getUsersWithPermission($parameters));
    }
}
