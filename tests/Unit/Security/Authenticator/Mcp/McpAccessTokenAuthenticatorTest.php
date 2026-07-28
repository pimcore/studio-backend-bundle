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
use Pimcore\Bundle\StudioBackendBundle\Security\Authenticator\Mcp\McpAccessTokenAuthenticator;
use Pimcore\Bundle\StudioBackendBundle\Security\Dto\ValidatedAccessToken;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\McpAccessTokenServiceInterface;
use Pimcore\Model\User;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

final class McpAccessTokenAuthenticatorTest extends Unit
{
    public function testSupportsOnlyBearerWithMcpPrefix(): void
    {
        $auth = $this->makeAuthenticator(null);
        $this->assertTrue($auth->supports($this->requestWith('Bearer pmcp_abc')));
        $this->assertFalse((bool) $auth->supports($this->requestWith('Bearer static-pat')));
        $this->assertFalse((bool) $auth->supports($this->requestWith('')));
    }

    public function testAuthenticateBuildsPassportAndBindsReferenceForValidToken(): void
    {
        $user = new User();
        $user->setUsername('agent-user');
        $validated = new ValidatedAccessToken($user, 'chat-1');
        $auth = $this->makeAuthenticator($validated);

        $request = $this->requestWith('Bearer pmcp_valid');
        $passport = $auth->authenticate($request);

        // The lookup - and with it the binding - now happens when the badge resolves,
        // which Symfony does during CheckPassportEvent: after the throttling peek and
        // still before the controller runs.
        $passport->getBadge(UserBadge::class)->getUser();

        $this->assertInstanceOf(SelfValidatingPassport::class, $passport);
        // Binds the token's reference on the request so downstream code (HasChatSession
        // trait) can trust it and ignore any forged X-Chat-Session-Id header.
        $this->assertSame('chat-1', $request->attributes->get('_mcp_token_reference'));
    }

    public function testAuthenticateReturnsAPassportForAnInvalidToken(): void
    {
        // Must not throw: AuthenticatorManager dispatches CheckPassportEvent only after
        // authenticate() returns, so an authenticator that throws here is unreachable by
        // LoginThrottlingListener's blocking peek. See PatAuthenticatorTest.
        $auth = $this->makeAuthenticator(null);

        $passport = $auth->authenticate($this->requestWith('Bearer pmcp_invalid'));

        $this->assertInstanceOf(SelfValidatingPassport::class, $passport);
    }

    public function testResolvingTheBadgeForAnInvalidTokenFails(): void
    {
        $auth = $this->makeAuthenticator(null);
        $passport = $auth->authenticate($this->requestWith('Bearer pmcp_invalid'));

        $this->expectException(UserNotFoundException::class);
        $passport->getBadge(UserBadge::class)->getUser();
    }

    public function testInvalidTokensAreNotBoundAsAReference(): void
    {
        $auth = $this->makeAuthenticator(null);
        $request = $this->requestWith('Bearer pmcp_invalid');

        $passport = $auth->authenticate($request);
        try {
            $passport->getBadge(UserBadge::class)->getUser();
        } catch (UserNotFoundException) {
            // expected
        }

        $this->assertNull($request->attributes->get('_mcp_token_reference'));
    }

    public function testFailureReturnsNullToFallThrough(): void
    {
        $auth = $this->makeAuthenticator(null);
        $this->assertNull($auth->onAuthenticationFailure(
            $this->requestWith('Bearer pmcp_x'),
            new AuthenticationException('nope'),
        ));
    }

    private function makeAuthenticator(?ValidatedAccessToken $validated): McpAccessTokenAuthenticator
    {
        return new McpAccessTokenAuthenticator(
            $this->makeEmpty(McpAccessTokenServiceInterface::class, [
                'validate' => $validated,
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
