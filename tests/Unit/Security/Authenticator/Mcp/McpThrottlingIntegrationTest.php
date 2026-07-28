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
use Pimcore\Bundle\StudioBackendBundle\Security\Service\McpAccessTokenServiceInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\RateLimiter\Storage\InMemoryStorage;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\TooManyLoginAttemptsAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Event\CheckPassportEvent;
use Symfony\Component\Security\Http\Event\LoginFailureEvent;
use Symfony\Component\Security\Http\EventListener\LoginThrottlingListener;
use Symfony\Component\Security\Http\RateLimiter\DefaultLoginRateLimiter;

/**
 * Drives the real LoginThrottlingListener against the real DefaultLoginRateLimiter, so
 * the interaction this bundle actually relies on is covered rather than just the shapes
 * the authenticators produce in isolation.
 *
 * The listener blocks on CheckPassportEvent and charges on LoginFailureEvent, exactly as
 * AuthenticatorManager sequences them.
 */
final class McpThrottlingIntegrationTest extends Unit
{
    private const int MAX_ATTEMPTS = 5;

    /** LoginThrottlingFactory derives the global tier as 5x max_attempts. */
    private const int GLOBAL_MAX_ATTEMPTS = self::MAX_ATTEMPTS * 5;

    private const string FIREWALL = 'pimcore_mcp';

    private const string CLIENT_IP = '203.0.113.42';

    private RequestStack $requestStack;

    /**
     * Dynamic tokens are keyed per token, so distinct guesses each land in their own
     * local bucket and it is the global per-IP tier - 5x max_attempts - that stops them.
     * That looser bound is the deliberate price of not letting a guesser evict a
     * legitimate token holder from the same IP; see the test below.
     */
    public function testInvalidGuessesAreBoundedByTheGlobalPerIpTier(): void
    {
        [$listener, $authenticator] = $this->createStack();

        for ($i = 0; $i < self::GLOBAL_MAX_ATTEMPTS; ++$i) {
            $this->attempt($listener, $authenticator, 'pmcp_guess-' . $i);
        }

        $this->expectException(TooManyLoginAttemptsAuthenticationException::class);
        $this->attempt($listener, $authenticator, 'pmcp_guess-final');
    }

    /**
     * Repeating one wrong token is caught by the tighter local tier, because identical
     * tokens share an identifier.
     */
    public function testRepeatingOneWrongTokenIsBoundedByTheLocalTier(): void
    {
        [$listener, $authenticator] = $this->createStack();

        for ($i = 0; $i < self::MAX_ATTEMPTS; ++$i) {
            $this->attempt($listener, $authenticator, 'pmcp_same-wrong-token');
        }

        $this->expectException(TooManyLoginAttemptsAuthenticationException::class);
        $this->attempt($listener, $authenticator, 'pmcp_same-wrong-token');
    }

    public function testInvalidGuessesDoNotLockOutAValidDynamicToken(): void
    {
        [$listener, $authenticator] = $this->createStack();

        // Exhaust the budget with wrong dynamic tokens from this IP.
        for ($i = 0; $i < self::MAX_ATTEMPTS; ++$i) {
            $this->attempt($listener, $authenticator, 'pmcp_guess-' . $i);
        }

        // A legitimate dynamic token from the same IP must still be let through to
        // validation. If every dynamic token shared one throttle identifier, the peek
        // above would already have exhausted this client's bucket too.
        // Reaching here without TooManyLoginAttemptsAuthenticationException is the
        // assertion: a distinct token must not inherit a guesser's bucket.
        $this->expectNotToPerformAssertions();
        $this->attempt($listener, $authenticator, 'pmcp_the-real-token');
    }

    /**
     * Runs one attempt through the listener the way AuthenticatorManager does: peek on
     * CheckPassportEvent, then charge on LoginFailureEvent.
     *
     * @throws TooManyLoginAttemptsAuthenticationException when the client is throttled
     */
    private function attempt(
        LoginThrottlingListener $listener,
        McpAccessTokenAuthenticator $authenticator,
        string $token
    ): void {
        $request = $this->createRequest($token);

        // checkPassport() writes LAST_USERNAME onto the RequestStack's *main* request and
        // DefaultLoginRateLimiter reads it back to key the local limiter, so the request
        // under test has to be the main one - exactly as it is when AuthenticatorManager
        // runs inside the kernel.
        $this->requestStack->push($request);

        try {
            $passport = $authenticator->authenticate($request);
            $listener->checkPassport(new CheckPassportEvent($authenticator, $passport));
            $listener->onFailedLogin($this->createFailureEvent($authenticator, $request, $passport));
        } finally {
            $this->requestStack->pop();
        }
    }

    /**
     * @return array{0: LoginThrottlingListener, 1: McpAccessTokenAuthenticator}
     */
    private function createStack(): array
    {
        $this->requestStack = new RequestStack();

        $limiter = new DefaultLoginRateLimiter(
            $this->createLimiterFactory('global', self::GLOBAL_MAX_ATTEMPTS),
            $this->createLimiterFactory('local', self::MAX_ATTEMPTS),
            'test-secret'
        );

        // validate() always fails: this test is about which bucket a request lands in,
        // not about resolving a user.
        $authenticator = new McpAccessTokenAuthenticator(
            $this->makeEmpty(McpAccessTokenServiceInterface::class, ['validate' => null])
        );

        return [new LoginThrottlingListener($this->requestStack, $limiter), $authenticator];
    }

    private function createLimiterFactory(string $id, int $limit): RateLimiterFactory
    {
        return new RateLimiterFactory(
            [
                'id' => 'test_mcp_' . $id,
                'policy' => 'fixed_window',
                'limit' => $limit,
                'interval' => '5 minutes',
            ],
            new InMemoryStorage()
        );
    }

    private function createRequest(string $token): Request
    {
        $request = Request::create('/pimcore-mcp/agent/documents', 'POST');
        $request->server->set('REMOTE_ADDR', self::CLIENT_IP);
        $request->headers->set('Authorization', 'Bearer ' . $token);

        return $request;
    }

    private function createFailureEvent(
        McpAccessTokenAuthenticator $authenticator,
        Request $request,
        Passport $passport
    ): LoginFailureEvent {
        return new LoginFailureEvent(
            new AuthenticationException('invalid'),
            $authenticator,
            $request,
            null,
            self::FIREWALL,
            $passport
        );
    }
}
