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
use Pimcore\Bundle\StaticResolverBundle\Lib\Tools\Authentication\AuthenticationResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Security\Authenticator\Mcp\PatAuthenticator;
use Pimcore\Bundle\StudioBackendBundle\Security\RateLimiter\McpLoginRateLimiter;
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

/**
 * Drives the real LoginThrottlingListener against the real McpLoginRateLimiter, so the
 * interaction this bundle relies on is covered rather than just the shapes PatAuthenticator
 * produces in isolation.
 *
 * The listener blocks on CheckPassportEvent and charges on LoginFailureEvent, exactly as
 * AuthenticatorManager sequences them.
 *
 * @internal
 */
final class McpLoginThrottlingIntegrationTest extends Unit
{
    private const int MAX_ATTEMPTS = 5;

    private const string FIREWALL = 'pimcore_mcp';

    private const string CLIENT_IP = '203.0.113.42';

    private const string VALID_TOKEN = 'good-token';

    private const string VALID_USERNAME = 'agent-user';

    private RequestStack $requestStack;

    public function testGuessesAreBlockedAfterTheConfiguredNumberOfFailures(): void
    {
        [$listener, $authenticator] = $this->createStack();

        for ($i = 0; $i < self::MAX_ATTEMPTS; ++$i) {
            $this->attemptLogin($listener, $authenticator, 'guess-' . $i);
        }

        $this->expectException(TooManyLoginAttemptsAuthenticationException::class);
        $this->attemptLogin($listener, $authenticator, 'guess-final');
    }

    /**
     * The requirement this limiter exists for: an exhausted guess budget must not reach a
     * client that authenticates successfully from the same address.
     *
     * With Symfony's DefaultLoginRateLimiter this fails - its derived per-IP tier is peeked
     * by every client on the address, so the guesses above would push this valid credential
     * into a 429.
     */
    public function testAValidCredentialIsNotBlockedByGuessesFromTheSameIp(): void
    {
        [$listener, $authenticator] = $this->createStack();

        for ($i = 0; $i < self::MAX_ATTEMPTS * 4; ++$i) {
            try {
                $this->attemptLogin($listener, $authenticator, 'guess-' . $i, charge: true);
            } catch (TooManyLoginAttemptsAuthenticationException) {
                // Blocked from attempt six onwards, and an attacker keeps hammering anyway.
                // The point of this test is what that does to *another* client, not to them.
            }
        }

        // Reaching the end without TooManyLoginAttemptsAuthenticationException is the
        // assertion: the valid credential carries a resolved identifier, which the limiter
        // hands no bucket to.
        $this->expectNotToPerformAssertions();
        $this->peek($listener, $authenticator, self::VALID_TOKEN);
    }

    /**
     * A valid credential that fails downstream - deactivated user, say - must not fill the
     * guess bucket either, or a legitimate client could lock out the guess detection.
     */
    public function testAResolvedIdentifierDoesNotChargeTheGuessBucket(): void
    {
        [$listener, $authenticator] = $this->createStack();

        for ($i = 0; $i < self::MAX_ATTEMPTS * 2; ++$i) {
            $this->attemptLogin($listener, $authenticator, self::VALID_TOKEN);
        }

        $this->expectNotToPerformAssertions();
        $this->peek($listener, $authenticator, 'first-guess-from-this-ip');
    }

    /**
     * Runs one attempt the way AuthenticatorManager does: peek on CheckPassportEvent, then
     * charge on LoginFailureEvent.
     *
     * @throws TooManyLoginAttemptsAuthenticationException when the client is throttled
     */
    private function attemptLogin(
        LoginThrottlingListener $listener,
        PatAuthenticator $authenticator,
        string $token,
        bool $charge = true
    ): void {
        $request = $this->createRequest($token);

        // checkPassport() writes LAST_USERNAME onto the RequestStack's *main* request and
        // the limiter reads it back, so the request under test has to be the main one -
        // exactly as it is when AuthenticatorManager runs inside the kernel.
        $this->requestStack->push($request);

        try {
            $passport = $authenticator->authenticate($request);
            $listener->checkPassport(new CheckPassportEvent($authenticator, $passport));

            if ($charge) {
                $listener->onFailedLogin($this->createFailureEvent($authenticator, $request, $passport));
            }
        } finally {
            $this->requestStack->pop();
        }
    }

    /**
     * @throws TooManyLoginAttemptsAuthenticationException when the client is throttled
     */
    private function peek(
        LoginThrottlingListener $listener,
        PatAuthenticator $authenticator,
        string $token
    ): void {
        $this->attemptLogin($listener, $authenticator, $token, charge: false);
    }

    /**
     * @return array{0: LoginThrottlingListener, 1: PatAuthenticator}
     */
    private function createStack(): array
    {
        $this->requestStack = new RequestStack();

        $limiter = new McpLoginRateLimiter(
            new RateLimiterFactory(
                [
                    'id' => 'test_mcp_login',
                    'policy' => 'fixed_window',
                    'limit' => self::MAX_ATTEMPTS,
                    'interval' => '5 minutes',
                ],
                new InMemoryStorage()
            )
        );

        $authenticator = new PatAuthenticator(
            $this->makeEmpty(AuthenticationResolverInterface::class, ['isValidUser' => true]),
            [self::VALID_USERNAME => [self::VALID_TOKEN]]
        );

        return [new LoginThrottlingListener($this->requestStack, $limiter), $authenticator];
    }

    private function createRequest(string $token): Request
    {
        $request = Request::create('/pimcore-mcp/agent/documents', 'POST');
        $request->server->set('REMOTE_ADDR', self::CLIENT_IP);
        $request->headers->set('Authorization', 'Bearer ' . $token);

        return $request;
    }

    private function createFailureEvent(
        PatAuthenticator $authenticator,
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
