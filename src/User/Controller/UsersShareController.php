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
use Pimcore\Bundle\StudioBackendBundle\User\Schema\SimpleUser;
use Pimcore\Bundle\StudioBackendBundle\User\Service\UserServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\UserPermissions;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\PaginatedResponseTrait;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @internal
 */
final class UsersShareController extends AbstractApiController
{
    use PaginatedResponseTrait;

    private const string ROUTE = '/users-share-list';

    public function __construct(
        SerializerInterface $serializer,
        private readonly UserServiceInterface $userService,
    ) {
        parent::__construct($serializer);
    }

    #[Route(self::ROUTE, name: 'pimcore_studio_api_users_share_list', methods: ['GET'])]
    #[IsGranted(UserPermissions::SHARE_CONFIGURATIONS->value)]
    #[Get(
        path: self::PREFIX . self::ROUTE,
        operationId: 'user_get_share_collection',
        description: 'user_get_share_collection_description',
        summary: 'user_get_share_collection_summary',
        tags: [Tags::User->value]
    )]
    #[SuccessResponse(
        description: 'user_get_share_collection_success_response',
        content: new CollectionJson(new GenericCollection(SimpleUser::class))
    )]
    #[DefaultResponses]
    public function getUsersShareList(): JsonResponse
    {
        $users = $this->userService->getUsers();

        return $this->getPaginatedCollection(
            $this->serializer,
            $users->getItems(),
            $users->getTotalItems()
        );
    }
}
