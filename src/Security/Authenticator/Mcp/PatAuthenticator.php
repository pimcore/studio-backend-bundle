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

namespace Pimcore\Bundle\StudioBackendBundle\Security\Authenticator\Mcp;

use Pimcore\Bundle\StaticResolverBundle\Lib\Tools\Authentication\AuthenticationResolverInterface;
use Pimcore\Model\User;
use Pimcore\Security\User\User as SecurityUser;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use function in_array;

/**
 * Authenticates MCP requests via Personal Access Tokens (config-based).
 *
 * Validates `Authorization: Bearer <token>` headers against
 * the `pimcore_studio_backend.mcp.authentication.tokens` configuration,
 * which maps Pimcore usernames to token lists.
 *
 * @internal
 */
class PatAuthenticator extends AbstractAuthenticator
{
    private const string AUTH_HEADER = 'Authorization';

    private const string BEARER_PREFIX = 'Bearer ';

    private const int BEARER_PREFIX_LENGTH = 7;

    /**
     * @param array<string, list<string>> $tokenMap username => [token, ...]
     */
    public function __construct(
        private readonly AuthenticationResolverInterface $authenticationResolver,
        private readonly array $tokenMap,
    ) {
    }

    public function supports(Request $request): ?bool
    {
        $authHeader = $request->headers->get(self::AUTH_HEADER, '');

        return str_starts_with($authHeader, self::BEARER_PREFIX);
    }

    public function authenticate(Request $request): Passport
    {
        $authHeader = $request->headers->get(self::AUTH_HEADER, '');
        $token = substr($authHeader, self::BEARER_PREFIX_LENGTH);

        if ($token === '') {
            throw new AuthenticationException('Bearer token is empty.');
        }

        $username = $this->resolveUsername($token);
        if ($username === null) {
            throw new AuthenticationException('Invalid bearer token.');
        }

        $pimcoreUser = User::getByName($username);
        if (!$pimcoreUser instanceof User) {
            throw new AuthenticationException('User not found for token.');
        }

        if (!$this->authenticationResolver->isValidUser($pimcoreUser)) {
            throw new AuthenticationException('User is not active.');
        }

        $userBadge = new UserBadge(
            $pimcoreUser->getUsername(),
            static fn () => new SecurityUser($pimcoreUser)
        );

        return new SelfValidatingPassport($userBadge);
    }

    public function onAuthenticationSuccess(
        Request $request,
        TokenInterface $token,
        string $firewallName
    ): ?Response {
        return null;
    }

    public function onAuthenticationFailure(
        Request $request,
        AuthenticationException $exception
    ): ?Response {
        return new JsonResponse(
            ['error' => $exception->getMessageKey()],
            Response::HTTP_UNAUTHORIZED
        );
    }

    private function resolveUsername(string $token): ?string
    {
        foreach ($this->tokenMap as $username => $tokens) {
            if (in_array($token, $tokens, true)) {
                return $username;
            }
        }

        return null;
    }
}
