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
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\DatabaseException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attributes\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attributes\Response\SuccessResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Schema\TreeNode;
use Pimcore\Bundle\StudioBackendBundle\User\Attributes\Request\CreateRequestBody;
use Pimcore\Bundle\StudioBackendBundle\User\MappedParameter\CreateParameter;
use Pimcore\Bundle\StudioBackendBundle\User\Service\UserServiceInterface;
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
final class CreateUserController extends AbstractApiController
{
    use PaginatedResponseTrait;

    public function __construct(
        SerializerInterface $serializer,
        private readonly UserServiceInterface $userService
    ) {
        parent::__construct($serializer);
    }

    /**
     * @throws DatabaseException|NotFoundException
     */
    #[Route('/user/', name: 'pimcore_studio_api_user_create', methods: ['POST'])]
    #[IsGranted(UserPermissions::USER_MANAGEMENT->value)]
    #[Post(
        path: self::API_PATH . '/user/',
        operationId: 'user_create',
        summary: 'Create a new user.',
        tags: [Tags::User->value]
    )]
    #[CreateRequestBody]
    #[SuccessResponse(
        description: 'Node of the new created User',
        content: new JsonContent(ref: TreeNode::class)
    )]
    #[DefaultResponses([
        HttpResponseCodes::NOT_FOUND,
    ])]
    public function createUser(#[MapRequestPayload] CreateParameter $createParameter): JsonResponse
    {
        $userNode = $this->userService->createUser($createParameter);

        return $this->jsonResponse($userNode);
    }
}
