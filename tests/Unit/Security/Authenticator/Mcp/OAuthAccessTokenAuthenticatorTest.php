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

    private function makeAuthenticator(bool $enabled, ?ResolvedAccess $resolved): OAuthAccessTokenAuthenticator
    {
        return new OAuthAccessTokenAuthenticator(
            $enabled,
            $this->makeEmpty(TokenValidatorInterface::class, ['validate' => $resolved]),
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
