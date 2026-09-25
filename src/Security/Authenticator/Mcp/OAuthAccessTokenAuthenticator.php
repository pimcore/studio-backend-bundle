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

use Pimcore\Bundle\StudioBackendBundle\Mcp\McpPath;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Contract\TokenValidatorInterface;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Util\CanonicalUri;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\McpAccessTokenService;
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
use function preg_match;
use function str_starts_with;
use function strlen;
use function substr;

/**
 * Authenticates MCP requests carrying an OAuth JWT access token
 * (`Authorization: Bearer <jwt>`). Additive to the existing chain: it only
 * claims JWT-shaped bearer tokens, declines the `pmcp_` prefix (handled by
 * McpAccessTokenAuthenticator), and stays inert unless the embedded OAuth
 * server is enabled. On failure it returns null so later authenticators
 * (e.g. PatAuthenticator) still run; hence it must sit before them in the chain.
 *
 * @internal
 */
final class OAuthAccessTokenAuthenticator extends AbstractAuthenticator
{
    private const string AUTH_HEADER = 'Authorization';

    private const string BEARER_PREFIX = 'Bearer ';

    private const string JWT_PATTERN = '/^[A-Za-z0-9_-]+\.[A-Za-z0-9_-]+\.[A-Za-z0-9_-]+$/u';

    public function __construct(
        private readonly bool $enabled,
        private readonly TokenValidatorInterface $tokenValidator,
        private readonly ?string $issuer = null,
    ) {
    }

    public function supports(Request $request): bool
    {
        // Inert without a configured issuer as well as when disabled. There is no safe
        // fallback for the audience to check against: see self::resourceUri().
        if (!$this->enabled || $this->issuer === null) {
            return false;
        }

        $token = $this->bearerToken($request);
        if ($token === null) {
            return false;
        }

        // pmcp_ tokens belong to McpAccessTokenAuthenticator; opaque PATs are
        // left to PatAuthenticator. Only claim JWT-shaped tokens.
        if (str_starts_with($token, McpAccessTokenService::TOKEN_PREFIX)) {
            return false;
        }

        return preg_match(self::JWT_PATTERN, $token) === 1;
    }

    public function authenticate(Request $request): Passport
    {
        $issuer = $this->issuer;
        if ($issuer === null) {
            // supports() has already declined; refusing again rather than reconstructing
            // an audience keeps the invariant in the code instead of in the wiring.
            throw new AuthenticationException('The OAuth issuer is not configured.');
        }

        $token = $this->bearerToken($request) ?? '';

        $resolved = $this->tokenValidator->validate($token, $this->resourceUri($issuer));
        if ($resolved === null) {
            throw new AuthenticationException('Invalid or expired OAuth access token.');
        }

        $user = $resolved->user;
        if (!$user instanceof User) {
            throw new AuthenticationException('Invalid or expired OAuth access token.');
        }

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
        // Return null so the next authenticator in the chain can try.
        return null;
    }

    private function bearerToken(Request $request): ?string
    {
        $header = $request->headers->get(self::AUTH_HEADER, '');
        if (!str_starts_with($header, self::BEARER_PREFIX)) {
            return null;
        }

        $token = substr($header, strlen(self::BEARER_PREFIX));

        return $token === '' ? null : $token;
    }

    /**
     * The configured issuer, not the request host. `Host` is caller-supplied unless
     * `framework.trusted_hosts` is set, and deriving the expected audience from it would
     * make this check compare an attacker's string against the same attacker's string.
     * The issuer is mandatory while OAuth is enabled, and the resource is contributed from
     * that same value, so the two agree by construction rather than by coincidence.
     *
     * There is deliberately no fallback to the request when the issuer is absent. Being
     * unregistered is not what refuses an audience: TokenValidatorInterface compares a
     * token's `aud` against the URI it is handed and never consults the resource registry,
     * so a request-derived URI here would be checked against itself and pass. The
     * configuration forbids a null issuer while OAuth is enabled, but this does not rely
     * on that - the authenticator simply declines.
     *
     * McpPath::BASE, not a local copy: the resource this validates against is the one
     * Mcp\ProtectedResourceProvider declares, and they must be the same string.
     */
    private function resourceUri(string $issuer): string
    {
        return CanonicalUri::canonicalize($issuer . McpPath::BASE);
    }
}
