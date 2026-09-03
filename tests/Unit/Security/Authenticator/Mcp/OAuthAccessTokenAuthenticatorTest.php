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
use Pimcore\Bundle\StudioBackendBundle\OAuth\Dto\ProtectedResource;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Dto\ResolvedAccess;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Resolver\RequestResourceResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Security\Authenticator\Mcp\OAuthAccessTokenAuthenticator;
use Pimcore\Model\User;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

final class OAuthAccessTokenAuthenticatorTest extends Unit
{
    private const string JWT = 'Bearer aaa.bbb.ccc';

    private const string RESOURCE = 'https://localhost/pimcore-mcp/studio/product-read';

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
        $resolved = new ResolvedAccess($user, ['mcp:read'], [self::RESOURCE], 'studio-mcp');

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

    public function testAuthenticateThrowsWhenTheEndpointIsNotARegisteredResource(): void
    {
        $user = new User();
        $user->setUsername('agent-user');
        $resolved = new ResolvedAccess($user, ['mcp:read'], [self::RESOURCE], 'studio-mcp');

        // An endpoint whose owner registered no protected resource has no audience a
        // token could carry, so the token is never even validated against one.
        $auth = $this->makeAuthenticator(true, $resolved, resource: null);

        $this->expectException(AuthenticationException::class);
        $auth->authenticate($this->requestWith(self::JWT));
    }

    public function testValidatesAgainstTheResourceTheRequestResolvesTo(): void
    {
        $user = new User();
        $user->setUsername('agent-user');
        $resolved = new ResolvedAccess($user, ['mcp:read'], [self::RESOURCE], 'studio-mcp');

        $seen = [];
        $auth = new OAuthAccessTokenAuthenticator(
            true,
            $this->makeEmpty(TokenValidatorInterface::class, [
                'validate' => function (string $token, string $resourceUri) use (&$seen, $resolved) {
                    $seen[] = $resourceUri;

                    return $resolved;
                },
            ]),
            $this->makeEmpty(RequestResourceResolverInterface::class, [
                'resolve' => new ProtectedResource(self::RESOURCE, [], []),
            ]),
        );

        $auth->authenticate($this->requestWith(self::JWT));

        // The audience checked is the one the endpoint's owner registered, not a
        // path this bundle derived for itself.
        $this->assertSame([self::RESOURCE], $seen);
    }

    private function makeAuthenticator(
        bool $enabled,
        ?ResolvedAccess $resolved,
        ?string $resource = self::RESOURCE,
    ): OAuthAccessTokenAuthenticator {
        return new OAuthAccessTokenAuthenticator(
            $enabled,
            $this->makeEmpty(TokenValidatorInterface::class, ['validate' => $resolved]),
            $this->makeEmpty(RequestResourceResolverInterface::class, [
                'resolve' => $resource === null ? null : new ProtectedResource($resource, [], []),
            ]),
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
