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

use Pimcore\Bundle\StudioBackendBundle\Security\Service\McpAccessTokenService;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\McpAccessTokenServiceInterface;
use Pimcore\Model\User;
use Pimcore\Security\User\User as SecurityUser;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use function str_starts_with;
use function strlen;
use function substr;

/**
 * Authenticates MCP requests carrying a dynamically-issued bearer token
 * (`Authorization: Bearer pmcp_…`). Never touches the PHP session.
 *
 * On success, the validated token's `reference` (chat session id) is stashed on the
 * request attributes (`_mcp_token_reference`) so downstream code (notably the
 * `HasChatSession` trait in the agent bundle) can trust it instead of the
 * spoofable `X-Chat-Session-Id` header.
 *
 * @internal
 */
final class McpAccessTokenAuthenticator extends AbstractAuthenticator
{
    public const string REQUEST_ATTR_REFERENCE = '_mcp_token_reference';

    private const string AUTH_HEADER = 'Authorization';

    private const string BEARER_PREFIX = 'Bearer ';

    public function __construct(
        private readonly McpAccessTokenServiceInterface $accessTokenService,
    ) {
    }

    public function supports(Request $request): bool
    {
        $header = $request->headers->get(self::AUTH_HEADER, '');

        return str_starts_with($header, self::BEARER_PREFIX . McpAccessTokenService::TOKEN_PREFIX);
    }

    public function authenticate(Request $request): Passport
    {
        $token = substr($request->headers->get(self::AUTH_HEADER, ''), strlen(self::BEARER_PREFIX));
        $validated = $this->accessTokenService->validate($token);

        if ($validated === null) {
            throw new AuthenticationException('Invalid or expired MCP access token.');
        }

        $user = $validated->user;
        if (!$user instanceof User) {
            throw new AuthenticationException('Invalid or expired MCP access token.');
        }

        $request->attributes->set(self::REQUEST_ATTR_REFERENCE, $validated->reference);

        return new SelfValidatingPassport(
            new UserBadge($user->getUsername(), static fn () => new SecurityUser($user)),
        );
    }

    public function onAuthenticationSuccess(
        Request $request,
        TokenInterface $token,
        string $firewallName,
    ): ?Response {
        return null;
    }

    public function onAuthenticationFailure(
        Request $request,
        AuthenticationException $exception,
    ): ?Response {
        // Return null so the next authenticator (PatAuthenticator) can try.
        return null;
    }
}
