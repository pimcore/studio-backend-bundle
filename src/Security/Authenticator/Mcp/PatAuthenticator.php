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
use Pimcore\Bundle\StudioBackendBundle\Security\Service\McpAccessTokenService;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\HttpResponseCodes;
use Pimcore\Model\User;
use Pimcore\Security\User\User as SecurityUser;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
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
    use McpThrottlingResponseTrait;

    private const string AUTH_HEADER = 'Authorization';

    private const string BEARER_PREFIX = 'Bearer ';

    private const int BEARER_PREFIX_LENGTH = 7;

    /**
     * Throttle bucket identifier for a credential that resolves to no user. Deliberately
     * constant: DefaultLoginRateLimiter keys its local limiter on identifier+IP, so a
     * per-token identifier would give every guess a fresh bucket and leave only the
     * global per-IP tier doing any work.
     */
    private const string INVALID_IDENTIFIER = '__invalid__';

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

        if (!str_starts_with($authHeader, self::BEARER_PREFIX)) {
            return false;
        }

        // Bearer tokens prefixed with the MCP access-token prefix are handled by
        // McpAccessTokenAuthenticator. Returning false here ensures Symfony's
        // authenticator chain does not run PAT validation in parallel — which
        // would override McpAccessTokenAuthenticator's success with a 401.
        if (str_starts_with($authHeader, self::BEARER_PREFIX . McpAccessTokenService::TOKEN_PREFIX)) {
            return false;
        }

        return true;
    }

    public function authenticate(Request $request): Passport
    {
        $authHeader = $request->headers->get(self::AUTH_HEADER, '');
        $token = substr($authHeader, self::BEARER_PREFIX_LENGTH);

        if ($token === '') {
            throw new AuthenticationException('Bearer token is empty.');
        }

        // The username comes from an in-memory token map, so it is known without any
        // database work. Building the badge here - rather than after the lookup - is what
        // lets LoginThrottlingListener::checkPassport() block a throttled client:
        // AuthenticatorManager dispatches CheckPassportEvent only *after* authenticate()
        // returns, so an authenticator that throws here can never be throttled.
        $username = $this->resolveUsername($token);

        return new SelfValidatingPassport(
            new UserBadge(
                $username ?? self::INVALID_IDENTIFIER,
                fn (): SecurityUser => $this->loadUser($username)
            )
        );
    }

    /**
     * Performs the actual lookup when the badge is resolved, which Symfony does during
     * CheckPassportEvent - after the throttling peek (priority 2080) and before the
     * controller runs.
     *
     * @throws UserNotFoundException
     */
    private function loadUser(?string $username): SecurityUser
    {
        if ($username === null) {
            throw new UserNotFoundException('Invalid bearer token.');
        }

        $pimcoreUser = User::getByName($username);
        if (!$pimcoreUser instanceof User) {
            throw new UserNotFoundException('User not found for token.');
        }

        if (!$this->authenticationResolver->isValidUser($pimcoreUser)) {
            throw new UserNotFoundException('User is not active.');
        }

        return new SecurityUser($pimcoreUser);
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
        // This is the last authenticator on the MCP firewall - the other two return null
        // to fall through to it - so it owns the terminal response. Returning null for an
        // ordinary failure would let an invalid credential continue unauthenticated and
        // leave the 401 to whatever role check the consuming bundle happens to declare.
        return $this->throttlingResponse($exception) ?? new JsonResponse(
            ['error' => $exception->getMessageKey()],
            HttpResponseCodes::UNAUTHORIZED->value
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
