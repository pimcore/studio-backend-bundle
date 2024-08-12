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

use OpenApi\Attributes\JsonContent;
use OpenApi\Attributes\Post;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\DatabaseException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attributes\Parameters\Path\IdParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attributes\Request\SingleParameterRequestBody;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attributes\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attributes\Response\SuccessResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Schema\TreeNode;
use Pimcore\Bundle\StudioBackendBundle\Role\MappedParameter\RoleCloneParameter;
use Pimcore\Bundle\StudioBackendBundle\Role\Service\RoleCloneServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constants\HttpResponseCodes;
use Pimcore\Bundle\StudioBackendBundle\Util\Constants\UserPermissions;
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
        path: self::API_PATH . '/role/clone/{id}',
        operationId: 'role_clone_by_id',
        summary: 'Clone a specific Role.',
        tags: [Tags::Role->value]
    )]
    #[SuccessResponse(
        description: 'Node of the cloned Role.',
        content: new JsonContent(ref: TreeNode::class)
    )]
    #[IdParameter(type: 'role')]
    #[SingleParameterRequestBody(
        parameterName: 'name',
        example: 'Cloned Role'
    )]
    #[DefaultResponses([
        HttpResponseCodes::NOT_FOUND,
    ])]
    public function cloneRole(int $id, #[MapRequestPayload] RoleCloneParameter $roleClone): JsonResponse
    {
        $roleNode = $this->roleCloneService->cloneRole($id, $roleClone->getName());

        return $this->jsonResponse($roleNode);
    }
}
