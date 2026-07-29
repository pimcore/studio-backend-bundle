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
use Pimcore\Bundle\StudioBackendBundle\Security\RateLimiter\McpLoginRateLimiterInterface;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\McpAccessTokenService;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\HttpResponseCodes;
use Pimcore\Model\User;
use Pimcore\Security\User\User as SecurityUser;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\TooManyLoginAttemptsAuthenticationException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use function in_array;
use function is_numeric;
use function max;

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

    private const int SECONDS_PER_MINUTE = 60;

    /**
     * Used when the throttling exception carries no threshold. Deliberately a separate
     * constant from SECONDS_PER_MINUTE despite the equal value - they mean different things.
     */
    private const int FALLBACK_RETRY_AFTER_SECONDS = 60;

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
        //
        // An unrecognised token carries the shared unknown-credential identifier, which is
        // the only identifier McpLoginRateLimiter hands a bucket to. A token that resolves
        // to a username carries that username instead and is therefore exempt.
        $username = $this->resolveUsername($token);

        return new SelfValidatingPassport(
            new UserBadge(
                $username ?? McpLoginRateLimiterInterface::UNKNOWN_CREDENTIAL_IDENTIFIER,
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

    /**
     * Maps login throttling to a machine-readable 429.
     *
     * TooManyLoginAttemptsAuthenticationException is an ordinary AuthenticationException, so
     * without this it would render as a bare 401 - telling an MCP client its credential is
     * wrong when the truth is "back off". MCP clients are programs that act on Retry-After.
     */
    private function throttlingResponse(AuthenticationException $exception): ?JsonResponse
    {
        if (!$exception instanceof TooManyLoginAttemptsAuthenticationException) {
            return null;
        }

        $response = new JsonResponse(
            ['error' => 'Too many failed authentication attempts. Please try again later.'],
            HttpResponseCodes::TOO_MANY_REQUESTS->value
        );
        $response->headers->set('Retry-After', (string) $this->retryAfterSeconds($exception));

        return $response;
    }

    /**
     * getMessageData() reports the threshold in minutes and it may be null. The threshold is
     * ceil((resetTime - now) / 60), which is 0 at a window boundary - advertising
     * "Retry-After: 0" would invite an immediate retry, so it is floored at one minute.
     */
    private function retryAfterSeconds(TooManyLoginAttemptsAuthenticationException $exception): int
    {
        $minutes = $exception->getMessageData()['%minutes%'] ?? null;

        return is_numeric($minutes)
            ? max(1, (int) $minutes) * self::SECONDS_PER_MINUTE
            : self::FALLBACK_RETRY_AFTER_SECONDS;
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
