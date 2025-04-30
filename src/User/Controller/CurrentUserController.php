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
use OpenApi\Attributes\JsonContent;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\SuccessResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Schema\UserInformation;
use Pimcore\Bundle\StudioBackendBundle\User\Service\UserInformationServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\HttpResponseCodes;
use Pimcore\Security\User\User;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

/**
 * @internal
 */
final class CurrentUserController extends AbstractApiController
{
    #[Route('/user/current-user-information', name: 'pimcore_studio_api_current_user', methods: ['GET'])]
    #[Get(
        path: self::PREFIX . '/user/current-user-information',
        operationId: 'user_get_current_information',
        summary: 'user_get_current_information_summary',
        tags: [Tags::User->value]
    )]
    #[SuccessResponse(
        description: 'user_get_current_information_success_response',
        content: new JsonContent(ref: UserInformation::class)
    )]
    #[DefaultResponses([
        HttpResponseCodes::UNAUTHORIZED,
    ])]
    public function getCurrentUserInformation(
        #[CurrentUser] User $user,
        UserInformationServiceInterface $userInformationService
    ): JsonResponse {
        return $this->jsonResponse(
            $userInformationService->getUserInformation($user->getUser())
        );
    }
}
