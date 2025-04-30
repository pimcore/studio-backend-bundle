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
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Property\GenericCollection;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\Content\CollectionJson;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\SuccessResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\Role\Schema\SimpleRole;
use Pimcore\Bundle\StudioBackendBundle\Role\Service\RoleServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\UserPermissions;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\PaginatedResponseTrait;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @internal
 */
final class GetRolesController extends AbstractApiController
{
    use PaginatedResponseTrait;

    public function __construct(
        SerializerInterface $serializer,
        private readonly RoleServiceInterface $roleService,
    ) {
        parent::__construct($serializer);
    }

    #[Route('/roles', name: 'pimcore_studio_api_roles', methods: ['GET'])]
    #[IsGranted(UserPermissions::USER_MANAGEMENT->value)]
    #[Get(
        path: self::PREFIX . '/roles',
        operationId: 'role_get_collection',
        description: 'role_get_collection_description',
        summary: 'role_get_collection_summary',
        tags: [Tags::Role->value]
    )]
    #[SuccessResponse(
        description: 'role_get_collection_success_response',
        content: new CollectionJson(new GenericCollection(SimpleRole::class))
    )]
    #[DefaultResponses]
    public function getRoles(): JsonResponse
    {
        $roles = $this->roleService->getRoles();

        return $this->getPaginatedCollection(
            $this->serializer,
            $roles->getItems(),
            $roles->getTotalItems()
        );
    }
}
