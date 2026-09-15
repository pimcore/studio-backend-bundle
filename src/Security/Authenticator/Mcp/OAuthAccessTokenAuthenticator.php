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

    private const string JWT_PATTERN = '/^[A-Za-z0-9_-]+\.[A-Za-z0-9_-]+\.[A-Za-z0-9_-]+$/';

    public function __construct(
        private readonly bool $enabled,
        private readonly TokenValidatorInterface $tokenValidator,
        private readonly ?string $issuer = null,
    ) {
    }

    public function supports(Request $request): bool
    {
        if (!$this->enabled) {
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
        $token = $this->bearerToken($request) ?? '';

        $resolved = $this->tokenValidator->validate($token, $this->resourceUri($request));
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
     * The issuer is mandatory while OAuth is enabled, and the resource is registered from
     * that same value, so the two agree by construction rather than by coincidence.
     *
     * The fallback is unreachable while enabled: a null issuer means OAuth is off, and
     * `supports()` has already declined. It also fails closed, since nothing can have
     * registered a resource for that host either.
     */
    private function resourceUri(Request $request): string
    {
        $base = $this->issuer ?? $request->getSchemeAndHttpHost();

        // McpPath::BASE, not a local copy: the resource this validates against is the one
        // Mcp\ProtectedResourceProvider declares, and they must be the same string.
        return CanonicalUri::canonicalize($base . McpPath::BASE);
    }
}
