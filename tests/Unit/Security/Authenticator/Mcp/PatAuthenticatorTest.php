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
use Pimcore\Bundle\StudioBackendBundle\Security\RateLimiter\McpLoginRateLimiterInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\TooManyLoginAttemptsAuthenticationException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

/**
 * The passport-shape assertions here exist for throttling, not for their own sake.
 *
 * AuthenticatorManager::executeAuthenticator() calls authenticate() and only *then*
 * dispatches CheckPassportEvent. LoginThrottlingListener::checkPassport() - the blocking
 * peek - listens on that event at priority 2080. An authenticator that throws inside
 * authenticate() therefore never produces a passport, the peek never runs, and
 * login_throttling can never block it; only LoginFailureEvent still fires, so failures
 * accumulate while nothing is ever rejected. Building the UserBadge before the lookup is
 * what makes the firewall's standard throttling usable here.
 */
final class PatAuthenticatorTest extends Unit
{
    private const string VALID_TOKEN = 'good-token';

    private const string VALID_USERNAME = 'agent-user';

    public function testSupportsOnlyPlainBearerTokens(): void
    {
        $auth = $this->makeAuthenticator([]);

        $this->assertTrue((bool) $auth->supports($this->requestWith('Bearer static-pat')));
        // pmcp_-prefixed tokens belong to McpAccessTokenAuthenticator.
        $this->assertSame(false, $auth->supports($this->requestWith('Bearer pmcp_abc')));
        $this->assertSame(false, $auth->supports($this->requestWith('')));
    }

    public function testAuthenticateReturnsAPassportEvenForAnUnknownToken(): void
    {
        $auth = $this->makeAuthenticator([]);

        $passport = $auth->authenticate($this->requestWith('Bearer unknown-token'));

        $this->assertInstanceOf(SelfValidatingPassport::class, $passport);
        // LoginThrottlingListener writes this identifier to the request's LAST_USERNAME
        // attribute, so it must never be the raw credential.
        $this->assertNotSame(
            'unknown-token',
            $passport->getBadge(UserBadge::class)->getUserIdentifier()
        );
    }

    public function testUnknownTokensAllShareOneThrottleIdentifier(): void
    {
        $auth = $this->makeAuthenticator([]);

        $first = $auth->authenticate($this->requestWith('Bearer guess-one'));
        $second = $auth->authenticate($this->requestWith('Bearer guess-two'));

        // A per-token identifier would hand every guess a fresh bucket, so a sweep would
        // never fill one. The shared identifier is what collapses the bucket to "unknown
        // credentials from this IP".
        $this->assertSame(
            $first->getBadge(UserBadge::class)->getUserIdentifier(),
            $second->getBadge(UserBadge::class)->getUserIdentifier()
        );

        // McpLoginRateLimiter hands out a bucket for exactly this identifier, so the two
        // classes have to agree on it.
        $this->assertSame(
            McpLoginRateLimiterInterface::UNKNOWN_CREDENTIAL_IDENTIFIER,
            $first->getBadge(UserBadge::class)->getUserIdentifier()
        );
    }

    public function testResolvingTheBadgeForAnUnknownTokenFails(): void
    {
        $auth = $this->makeAuthenticator([]);
        $passport = $auth->authenticate($this->requestWith('Bearer unknown-token'));

        $this->expectException(UserNotFoundException::class);
        $passport->getBadge(UserBadge::class)->getUser();
    }

    public function testKnownTokenResolvesToItsUsername(): void
    {
        $auth = $this->makeAuthenticator([self::VALID_USERNAME => [self::VALID_TOKEN]]);

        $passport = $auth->authenticate($this->requestWith('Bearer ' . self::VALID_TOKEN));

        // Only the identifier is asserted: resolving the badge would call
        // User::getByName(), which needs a database. Identifier resolution is the part
        // this authenticator owns.
        $this->assertSame(
            self::VALID_USERNAME,
            $passport->getBadge(UserBadge::class)->getUserIdentifier()
        );
    }

    public function testEmptyBearerValueStillThrows(): void
    {
        // An absent credential is not a guess and carries no identifier worth bucketing,
        // so it is rejected before any passport is built.
        $auth = $this->makeAuthenticator([]);

        $this->expectException(AuthenticationException::class);
        $auth->authenticate($this->requestWith('Bearer '));
    }

    public function testOrdinaryFailureStillReturnsA401(): void
    {
        // PatAuthenticator is the last authenticator on the MCP firewall - the other two
        // return null to fall through to it - so it owns the terminal response. Returning
        // null here would let an invalid PAT continue unauthenticated and leave the 401 to
        // whatever role check a consumer bundle happens to have.
        $response = $this->makeAuthenticator([])->onAuthenticationFailure(
            $this->requestWith('Bearer wrong'),
            new AuthenticationException('nope')
        );

        $this->assertNotNull($response);
        $this->assertSame(401, $response->getStatusCode());
    }

    public function testThrottlingFailureReturnsA429NotA401(): void
    {
        $response = $this->makeAuthenticator([])->onAuthenticationFailure(
            $this->requestWith('Bearer wrong'),
            new TooManyLoginAttemptsAuthenticationException(5)
        );

        $this->assertNotNull($response);
        $this->assertSame(429, $response->getStatusCode());
        // The exception reports minutes; Retry-After is defined in seconds.
        $this->assertSame('300', $response->headers->get('Retry-After'));
    }

    public function testRetryAfterIsFlooredAtOneMinuteAtAWindowBoundary(): void
    {
        // LoginThrottlingListener computes the threshold as ceil((resetTime - now) / 60),
        // which is 0 when the window is about to roll. Advertising "Retry-After: 0" would
        // invite an immediate retry.
        $response = $this->makeAuthenticator([])->onAuthenticationFailure(
            $this->requestWith('Bearer wrong'),
            new TooManyLoginAttemptsAuthenticationException(0)
        );

        $this->assertNotNull($response);
        $this->assertSame('60', $response->headers->get('Retry-After'));
    }

    public function testRetryAfterFallsBackWhenNoThresholdIsGiven(): void
    {
        $response = $this->makeAuthenticator([])->onAuthenticationFailure(
            $this->requestWith('Bearer wrong'),
            new TooManyLoginAttemptsAuthenticationException()
        );

        $this->assertNotNull($response);
        $this->assertSame('60', $response->headers->get('Retry-After'));
    }

    /**
     * @param array<string, list<string>> $tokenMap
     */
    private function makeAuthenticator(array $tokenMap): PatAuthenticator
    {
        $resolver = $this->makeEmpty(AuthenticationResolverInterface::class, [
            'isValidUser' => true,
        ]);

        return new PatAuthenticator($resolver, $tokenMap);
    }

    private function requestWith(string $authorization): Request
    {
        $request = Request::create('/pimcore-mcp/agent/documents', 'POST');
        $request->headers->set('Authorization', $authorization);

        return $request;
    }
}
