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

use OpenApi\Attributes\JsonContent;
use OpenApi\Attributes\Post;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\DatabaseException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Parameter\Path\IdParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Request\SingleParameterRequestBody;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\SuccessResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Schema\TreeNode;
use Pimcore\Bundle\StudioBackendBundle\Role\MappedParameter\RoleCloneParameter;
use Pimcore\Bundle\StudioBackendBundle\Role\Service\RoleCloneServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\HttpResponseCodes;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\UserPermissions;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @internal
 */
final class CloneController extends AbstractApiController
{
    public function __construct(
        SerializerInterface $serializer,
        private readonly RoleCloneServiceInterface $roleCloneService
    ) {
        parent::__construct($serializer);
    }

    /**
     * @throws DatabaseException|NotFoundException
     */
    #[Route('/role/clone/{id}', name: 'pimcore_studio_api_role_clone', methods: ['POST'])]
    #[IsGranted(UserPermissions::USER_MANAGEMENT->value)]
    #[Post(
        path: self::PREFIX . '/role/clone/{id}',
        operationId: 'role_clone_by_id',
        summary: 'role_clone_by_id_summary',
        tags: [Tags::Role->value]
    )]
    #[SuccessResponse(
        description: 'role_clone_by_id_success_response',
        content: new JsonContent(ref: TreeNode::class)
    )]
    #[IdParameter(type: 'role')]
    #[SingleParameterRequestBody(
        parameterName: 'name',
        example: 'Cloned Role'
    )]
    #[DefaultResponses([
        HttpResponseCodes::NOT_FOUND,
        HttpResponseCodes::INTERNAL_SERVER_ERROR,
    ])]
    public function cloneRole(int $id, #[MapRequestPayload] RoleCloneParameter $roleClone): JsonResponse
    {
        $roleNode = $this->roleCloneService->cloneRole($id, $roleClone->getName());

        return $this->jsonResponse($roleNode);
    }
}
