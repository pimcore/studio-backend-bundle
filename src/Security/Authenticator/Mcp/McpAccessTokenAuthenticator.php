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
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
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
    use McpThrottlingResponseTrait;

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

        // validate() *is* the lookup, so no real identifier exists before it runs. The
        // badge is still built first: AuthenticatorManager dispatches CheckPassportEvent
        // only after authenticate() returns, so throwing here would put this
        // authenticator out of reach of LoginThrottlingListener's blocking peek. Every
        // request therefore carries the placeholder identifier and relies on the global
        // per-IP tier, which is exactly the breadth-first defence it exists for.
        return new SelfValidatingPassport(
            new UserBadge(
                PatAuthenticator::INVALID_IDENTIFIER,
                fn (): SecurityUser => $this->loadUser($token, $request)
            ),
        );
    }

    /**
     * Resolves the token when the badge is resolved - during CheckPassportEvent, after
     * the throttling peek and before the controller runs.
     *
     * @throws UserNotFoundException
     */
    private function loadUser(string $token, Request $request): SecurityUser
    {
        $validated = $this->accessTokenService->validate($token);

        if ($validated === null || !$validated->user instanceof User) {
            throw new UserNotFoundException('Invalid or expired MCP access token.');
        }

        // Bind the token's own reference so HasChatSession can trust it over any forged
        // X-Chat-Session-Id header. Only ever set for a token that actually validated.
        $request->attributes->set(self::REQUEST_ATTR_REFERENCE, $validated->reference);

        return new SecurityUser($validated->user);
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
        return $this->throttlingResponse($exception);
    }
}
