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

namespace Pimcore\Bundle\StudioBackendBundle\Authorization\Controller;

use OpenApi\Attributes\JsonContent;
use OpenApi\Attributes\Post;
use Pimcore\Bundle\StudioBackendBundle\Authorization\Attribute\Request\CredentialsRequestBody;
use Pimcore\Bundle\StudioBackendBundle\Authorization\Attribute\Response\InvalidCredentialsResponse;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\SuccessResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Schema\UserInformation;
use Pimcore\Security\User\User;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

/**
 * @internal
 */
final class LoginController extends AbstractApiController
{
    #[Route('/login', name: 'pimcore_studio_api_login', methods: ['POST'])]
    #[Post(
        path: self::PREFIX . '/login',
        operationId: 'login',
        description: 'login_description',
        summary: 'login_summary',
        tags: [Tags::Authorization->name]
    )]
    #[CredentialsRequestBody]
    #[SuccessResponse(
        description: 'login_success_response',
        content: new JsonContent(ref: UserInformation::class)
    )]
    #[InvalidCredentialsResponse]
    #[DefaultResponses]
    public function login(#[CurrentUser] User $user): JsonResponse
    {
        return $this->jsonResponse([
            'username' => $user->getUserIdentifier(),
            'roles' => $user->getRoles(),
        ]);
    }
}
