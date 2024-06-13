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

use OpenApi\Attributes\JsonContent;
use OpenApi\Attributes\Post;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\Exception\DatabaseException;
use Pimcore\Bundle\StudioBackendBundle\Exception\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attributes\Parameters\Path\IdParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attributes\Request\SingleParameterRequestBody;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attributes\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attributes\Response\SuccessResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\User\MappedParameter\UserCloneParameter;
use Pimcore\Bundle\StudioBackendBundle\User\Schema\UserTreeNode;
use Pimcore\Bundle\StudioBackendBundle\User\Service\UserCloneServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constants\HttpResponseCodes;
use Pimcore\Bundle\StudioBackendBundle\Util\Constants\UserPermissions;
use Pimcore\Bundle\StudioBackendBundle\Util\Traits\PaginatedResponseTrait;
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
    use PaginatedResponseTrait;
    public function __construct(
        SerializerInterface $serializer,
        private readonly UserCloneServiceInterface $userCloneService
    ) {
        parent::__construct($serializer);
    }


    /**
     * @throws DatabaseException|NotFoundException
     */
    #[Route('/user/clone/{id}', name: 'pimcore_studio_api_user_clone', methods: ['POST'])]
    #[IsGranted(UserPermissions::USER_MANAGEMENT->value)]
    #[Post(
        path: self::API_PATH . '/user/clone/{id}',
        operationId: 'cloneUser',
        summary: 'Clone a specific user.',
        tags: [Tags::User->value]
    )]
    #[SuccessResponse(
        description: 'Node of the cloned user.',
        content: new JsonContent(ref: UserTreeNode::class)
    )]
    #[IdParameter(type: 'user')]
    #[SingleParameterRequestBody(
        parameterName: 'name',
        example: 'Cloned User'
    )]
    #[DefaultResponses([
        HttpResponseCodes::NOT_FOUND
    ])]
    public function cloneUser(int $id, #[MapRequestPayload] UserCloneParameter $userClone): JsonResponse
    {
        $userNode = $this->userCloneService->cloneUser($id, $userClone->getName());
        return $this->jsonResponse($userNode);
    }
}
