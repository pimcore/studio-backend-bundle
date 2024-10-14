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
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\DatabaseException;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\ParentIdParameter as MappedParentIdParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Parameter\Query\ParentIdParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Property\GenericCollection;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\Content\CollectionJson;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\SuccessResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Schema\TreeNode;
use Pimcore\Bundle\StudioBackendBundle\Role\Service\RoleServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\UserPermissions;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\PaginatedResponseTrait;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @internal
 */
final class RoleTreeController extends AbstractApiController
{
    use PaginatedResponseTrait;

    public function __construct(
        SerializerInterface $serializer,
        private readonly RoleServiceInterface $roleService
    ) {
        parent::__construct($serializer);
    }

    /**
     * @throws DatabaseException
     */
    #[Route('/roles/tree', name: 'pimcore_studio_api_role_tree', methods: ['GET'])]
    #[IsGranted(UserPermissions::USER_MANAGEMENT->value)]
    #[Get(
        path: self::PREFIX . '/roles/tree',
        operationId: 'role_get_tree',
        summary: 'role_get_tree_summary',
        tags: [Tags::Role->value]
    )]
    #[ParentIdParameter(
        description: 'Filter roles by parent id.',
        required: true,
        minimum: 0,
        example: 0
    )]
    #[SuccessResponse(
        description: 'role_get_tree_success_response',
        content: new CollectionJson(new GenericCollection(TreeNode::class))
    )]
    #[DefaultResponses]
    public function getRoleTree(#[MapQueryString] MappedParentIdParameter $roleTreeListingParameter): Response
    {
        $roles = $this->roleService->getRoleTreeCollection($roleTreeListingParameter);

        return $this->getPaginatedCollection($this->serializer, $roles->getItems(), $roles->getTotalItems());
    }
}
