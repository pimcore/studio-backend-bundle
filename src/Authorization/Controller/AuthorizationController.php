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

namespace Pimcore\Bundle\StudioApiBundle\Authorization\Controller;

use OpenApi\Attributes\JsonContent;
use OpenApi\Attributes\Post;
use Pimcore\Bundle\StudioApiBundle\Attributes\Request\CredentialsRequestBody;
use Pimcore\Bundle\StudioApiBundle\Attributes\Request\TokenRequestBody;
use Pimcore\Bundle\StudioApiBundle\Attributes\Response\Error\MethodNotAllowedResponse;
use Pimcore\Bundle\StudioApiBundle\Attributes\Response\Error\UnauthorizedResponse;
use Pimcore\Bundle\StudioApiBundle\Attributes\Response\Error\UnprocessableContentResponse;
use Pimcore\Bundle\StudioApiBundle\Attributes\Response\Error\UnsupportedMediaTypeResponse;
use Pimcore\Bundle\StudioApiBundle\Attributes\Response\SuccessResponse;
use Pimcore\Bundle\StudioApiBundle\Authorization\Schema\Credentials;
use Pimcore\Bundle\StudioApiBundle\Authorization\Schema\Refresh;
use Pimcore\Bundle\StudioApiBundle\Authorization\Schema\Token;
use Pimcore\Bundle\StudioApiBundle\Authorization\Service\TokenServiceInterface;
use Pimcore\Bundle\StudioApiBundle\Config\Tags;
use Pimcore\Bundle\StudioApiBundle\Controller\Api\AbstractApiController;
use Pimcore\Bundle\StudioApiBundle\Service\SecurityServiceInterface;
use Pimcore\Security\User\User;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @internal
 */
final class AuthorizationController extends AbstractApiController
{
    public function __construct(
        private readonly SecurityServiceInterface $securityService,
        private readonly TokenServiceInterface $tokenService,
        SerializerInterface $serializer
    ) {
        parent::__construct($serializer);
    }

    #[Route('/login', name: 'pimcore_studio_api_login', methods: ['POST'])]
    #[POST(
        path: self::API_PATH . '/login',
        operationId: 'login',
        summary: 'Login with user credentials and get access token',
        tags: [Tags::Authorization->name]
    )]
    #[CredentialsRequestBody]
    #[SuccessResponse(
        description: 'Token, lifetime and user identifier',
        content: new JsonContent(ref: Token::class)
    )]
    #[UnauthorizedResponse]
    #[MethodNotAllowedResponse]
    public function login(#[MapRequestPayload] Credentials $credentials): JsonResponse
    {
        /** @var User $user */
        $user = $this->securityService->authenticateUser($credentials);

        $token = $this->tokenService->generateAndSaveToken($user->getUserIdentifier());

        return $this->jsonResponse(new Token($token, $this->tokenService->getLifetime(), $user->getUserIdentifier()));
    }

    #[Route('/refresh', name: 'pimcore_studio_api_refresh', methods: ['POST'])]
    #[POST(
        path: self::API_PATH . '/refresh',
        operationId: 'refresh',
        summary: 'Login with user credentials and get access token',
        tags: ['Authorization']
    )]
    #[TokenRequestBody]
    #[SuccessResponse(
        description: 'Token, lifetime and user identifier',
        content: new JsonContent(ref: Token::class)
    )]
    #[UnauthorizedResponse]
    #[MethodNotAllowedResponse]
    #[UnsupportedMediaTypeResponse]
    #[UnprocessableContentResponse]
    public function refresh(#[MapRequestPayload] Refresh $refresh): JsonResponse
    {
        $tokenInfo = $this->tokenService->refreshToken($refresh->getToken());

        return $this->jsonResponse(
            new Token(
                $tokenInfo->getToken(),
                $this->tokenService->getLifetime(),
                $tokenInfo->getUsername())
        );
    }
}
