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

use OpenApi\Attributes\JsonContent;
use OpenApi\Attributes\Put;
use OpenApi\Attributes\RequestBody;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\DatabaseException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ParseException;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\SuccessResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Schema\UserInformation;
use Pimcore\Bundle\StudioBackendBundle\User\Schema\UpdateUserProfile;
use Pimcore\Bundle\StudioBackendBundle\User\Service\UserInformationServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\User\Service\UserUpdateServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\HttpResponseCodes;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\PaginatedResponseTrait;
use Pimcore\Security\User\User;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @internal
 */
final class UpdateProfileController extends AbstractApiController
{
    use PaginatedResponseTrait;

    public function __construct(
        SerializerInterface $serializer,
        private readonly UserUpdateServiceInterface $userUpdateService,
        private readonly UserInformationServiceInterface $userInformationService
    ) {
        parent::__construct($serializer);
    }

    /**
     * @throws DatabaseException|ParseException
     */
    #[Route('/user/update-profile', name: 'pimcore_studio_api_user_update_profile', methods: ['PUT'])]
    #[Put(
        path: self::PREFIX . '/user/update-profile',
        operationId: 'user_update_profile',
        description: 'user_update_profile_description',
        summary: 'user_update_profile_summary',
        tags: [Tags::User->value]
    )]
    #[SuccessResponse(
        description: 'user_update_profile_success_response',
        content: new JsonContent(ref: UserInformation::class)
    )]
    #[RequestBody(
        content: new JsonContent(ref: UpdateUserProfile::class)
    )]
    #[DefaultResponses([
        HttpResponseCodes::INTERNAL_SERVER_ERROR,
        HttpResponseCodes::UNAUTHORIZED,
    ])]
    public function updateUserProfile(
        #[CurrentUser] User $user,
        #[MapRequestPayload] UpdateUserProfile $params
    ): JsonResponse {
        $this->userUpdateService->updateUserProfile($user->getUser(), $params);

        return $this->jsonResponse($this->userInformationService->getUserInformation($user->getUser()));
    }
}
