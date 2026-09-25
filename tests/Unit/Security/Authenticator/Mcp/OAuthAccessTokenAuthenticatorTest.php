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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\Security\Authenticator\Mcp;

use Codeception\Test\Unit;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Contract\TokenValidatorInterface;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Dto\ResolvedAccess;
use Pimcore\Bundle\StudioBackendBundle\Security\Authenticator\Mcp\OAuthAccessTokenAuthenticator;
use Pimcore\Model\User;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

final class OAuthAccessTokenAuthenticatorTest extends Unit
{
    private const string JWT = 'Bearer aaa.bbb.ccc';

    public function testDisabledNeverSupports(): void
    {
        $auth = $this->makeAuthenticator(false, null);
        $this->assertFalse((bool) $auth->supports($this->requestWith(self::JWT)));
    }

    public function testSupportsOnlyJwtShapedNonPrefixedBearer(): void
    {
        $auth = $this->makeAuthenticator(true, null);
        $this->assertTrue((bool) $auth->supports($this->requestWith(self::JWT)));
        // pmcp_ handled by McpAccessTokenAuthenticator; opaque PATs by PatAuthenticator.
        $this->assertFalse((bool) $auth->supports($this->requestWith('Bearer pmcp_abc.def.ghi')));
        $this->assertFalse((bool) $auth->supports($this->requestWith('Bearer static-pat')));
        $this->assertFalse((bool) $auth->supports($this->requestWith('')));
    }

    public function testAuthenticateBuildsPassportForValidToken(): void
    {
        $user = new User();
        $user->setUsername('agent-user');
        $resolved = new ResolvedAccess($user, ['mcp:read'], ['https://localhost/pimcore-mcp'], 'studio-mcp');

        $passport = $this->makeAuthenticator(true, $resolved)->authenticate($this->requestWith(self::JWT));

        $this->assertInstanceOf(SelfValidatingPassport::class, $passport);
    }

    public function testAuthenticateThrowsForInvalidToken(): void
    {
        $auth = $this->makeAuthenticator(true, null);
        $this->expectException(AuthenticationException::class);
        $auth->authenticate($this->requestWith(self::JWT));
    }

    public function testFailureReturnsNullToFallThrough(): void
    {
        $auth = $this->makeAuthenticator(true, null);
        $this->assertNull(
            $auth->onAuthenticationFailure($this->requestWith(self::JWT), new AuthenticationException('nope'))
        );
    }

    /**
     * The audience a token is checked against must come from configuration, not from the
     * request. `Host` is caller-supplied unless `trusted_hosts` is set, so deriving it
     * from the request would compare an attacker's string against the same attacker's
     * string and pass.
     */
    public function testValidatesAgainstTheConfiguredIssuerNotTheRequestHost(): void
    {
        $seen = [];
        $auth = new OAuthAccessTokenAuthenticator(
            true,
            $this->makeEmpty(TokenValidatorInterface::class, [
                'validate' => function (string $token, string $resourceUri) use (&$seen): ?ResolvedAccess {
                    $seen[] = $resourceUri;

                    return null;
                },
            ]),
            'https://pimcore.example.com',
        );

        $request = $this->requestWith(self::JWT);
        $request->headers->set('Host', 'evil.example');

        try {
            $auth->authenticate($request);
        } catch (AuthenticationException) {
            // The validator returned null; only the resource URI it was asked about matters.
        }

        $this->assertSame(['https://pimcore.example.com/pimcore-mcp'], $seen);
    }

    /**
     * Without a configured issuer there is no audience to check against, and falling back
     * to the request would rebuild the caller-controlled value the issuer pinning exists
     * to remove. Being unregistered is not what saves it: TokenValidatorInterface compares
     * a token's `aud` against the URI it is handed and never consults the resource
     * registry, so a request-derived URI would be compared with itself and pass.
     */
    public function testDeclinesWhenNoIssuerIsConfigured(): void
    {
        $auth = new OAuthAccessTokenAuthenticator(
            true,
            $this->makeEmpty(TokenValidatorInterface::class, ['validate' => null]),
            null,
        );

        $this->assertFalse($auth->supports($this->requestWith(self::JWT)));
    }

    /**
     * And refuses outright if reached anyway, so the invariant does not depend on the
     * wiring that makes supports() the only caller.
     */
    public function testAuthenticateRefusesWhenNoIssuerIsConfigured(): void
    {
        $validated = false;
        $auth = new OAuthAccessTokenAuthenticator(
            true,
            $this->makeEmpty(TokenValidatorInterface::class, [
                'validate' => function () use (&$validated): ?ResolvedAccess {
                    $validated = true;

                    return null;
                },
            ]),
            null,
        );

        $this->expectException(AuthenticationException::class);

        try {
            $auth->authenticate($this->requestWith(self::JWT));
        } finally {
            $this->assertFalse($validated, 'No audience may be built from the request.');
        }
    }

    private function makeAuthenticator(bool $enabled, ?ResolvedAccess $resolved): OAuthAccessTokenAuthenticator
    {
        return new OAuthAccessTokenAuthenticator(
            $enabled,
            $this->makeEmpty(TokenValidatorInterface::class, ['validate' => $resolved]),
            'https://pimcore.example.com',
        );
    }

    private function requestWith(string $authHeader): Request
    {
        $request = new Request();
        if ($authHeader !== '') {
            $request->headers->set('Authorization', $authHeader);
        }

        return $request;
    }
}
