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

namespace Pimcore\Bundle\StudioBackendBundle\ServiceUser\Controller;

use OpenApi\Attributes\JsonContent;
use OpenApi\Attributes\Post;
use OpenApi\Attributes\RequestBody;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\DatabaseException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ElementExistsException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Parameter\Path\IdParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\SuccessResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\ServiceUser\MappedParameter\CreateFromUserParameter;
use Pimcore\Bundle\StudioBackendBundle\ServiceUser\Schema\ServiceUserCredentials;
use Pimcore\Bundle\StudioBackendBundle\ServiceUser\Service\ServiceUserServiceInterface;
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
final class CreateFromUserController extends AbstractApiController
{
    public function __construct(
        SerializerInterface $serializer,
        private readonly ServiceUserServiceInterface $serviceUserService,
    ) {
        parent::__construct($serializer);
    }

    /**
     * @throws DatabaseException|ElementExistsException|ForbiddenException|InvalidArgumentException|NotFoundException
     */
    #[Route(
        '/service-user/from-user/{id}',
        name: 'pimcore_studio_api_service_user_create_from_user',
        requirements: ['id' => '\d+'],
        methods: ['POST']
    )]
    #[IsGranted(UserPermissions::USER_MANAGEMENT->value)]
    #[Post(
        path: self::PREFIX . '/service-user/from-user/{id}',
        operationId: 'service_user_create_from_user',
        description: 'service_user_create_from_user_description',
        summary: 'service_user_create_from_user_summary',
        tags: [Tags::ServiceUser->value]
    )]
    #[IdParameter(type: 'user')]
    #[RequestBody(content: new JsonContent(ref: CreateFromUserParameter::class))]
    #[SuccessResponse(
        description: 'service_user_create_from_user_success_response',
        content: new JsonContent(ref: ServiceUserCredentials::class)
    )]
    #[DefaultResponses([
        HttpResponseCodes::NOT_FOUND,
        HttpResponseCodes::FORBIDDEN,
        HttpResponseCodes::CONFLICT,
        HttpResponseCodes::UNPROCESSABLE_CONTENT,
    ])]
    public function createServiceUserFromUser(
        int $id,
        #[MapRequestPayload] CreateFromUserParameter $parameter
    ): JsonResponse {
        return $this->jsonResponse($this->serviceUserService->createServiceUserFromUser($id, $parameter));
    }
}
