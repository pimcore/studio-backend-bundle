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
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Parameter\Path\IdParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\SuccessResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\ServiceUser\Schema\ServiceUserCredentials;
use Pimcore\Bundle\StudioBackendBundle\ServiceUser\Service\ServiceUserServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\HttpResponseCodes;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\UserPermissions;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @internal
 */
final class RotateSecretController extends AbstractApiController
{
    public function __construct(
        SerializerInterface $serializer,
        private readonly ServiceUserServiceInterface $serviceUserService,
    ) {
        parent::__construct($serializer);
    }

    /**
     * @throws NotFoundException
     */
    #[Route(
        '/service-user/{id}/rotate-secret',
        name: 'pimcore_studio_api_service_user_rotate_secret',
        requirements: ['id' => '\d+'],
        methods: ['POST']
    )]
    #[IsGranted(UserPermissions::USER_MANAGEMENT->value)]
    #[Post(
        path: self::PREFIX . '/service-user/{id}/rotate-secret',
        operationId: 'service_user_rotate_secret',
        description: 'service_user_rotate_secret_description',
        summary: 'service_user_rotate_secret_summary',
        tags: [Tags::ServiceUser->value]
    )]
    #[IdParameter(type: 'service user')]
    #[SuccessResponse(
        description: 'service_user_rotate_secret_success_response',
        content: new JsonContent(ref: ServiceUserCredentials::class)
    )]
    #[DefaultResponses([
        HttpResponseCodes::NOT_FOUND,
    ])]
    public function rotateSecret(int $id): JsonResponse
    {
        return $this->jsonResponse($this->serviceUserService->rotateSecret($id));
    }
}
