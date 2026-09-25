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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\Security\RateLimiter;

use Codeception\Test\Unit;
use Pimcore\Bundle\StudioBackendBundle\Security\RateLimiter\McpLoginRateLimiter;
use Pimcore\Bundle\StudioBackendBundle\Security\RateLimiter\McpLoginRateLimiterInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\RateLimiter\Storage\InMemoryStorage;
use Symfony\Component\Security\Http\SecurityRequestAttributes;

/**
 * @internal
 */
final class McpLoginRateLimiterTest extends Unit
{
    private const int MAX_ATTEMPTS = 5;

    private const string CLIENT_IP = '203.0.113.42';

    /**
     * The whole point of the limiter: a credential that resolved to a user gets no bucket,
     * so no amount of unrelated failures can make its peek fail.
     *
     * Remaining tokens rather than isAccepted() is the discriminator here because that is
     * what LoginThrottlingListener::checkPassport() tests - a zero-token peek on an
     * exhausted fixed window is still "accepted".
     */
    public function testAResolvedIdentifierIsExempt(): void
    {
        $limiter = $this->createLimiter();
        $attacker = $this->createRequest(McpLoginRateLimiterInterface::UNKNOWN_CREDENTIAL_IDENTIFIER);

        for ($i = 0; $i < self::MAX_ATTEMPTS; ++$i) {
            $limiter->consume($attacker);
        }

        $this->assertSame(0, $limiter->peek($attacker)->getRemainingTokens());
        $this->assertGreaterThan(0, $limiter->peek($this->createRequest('agent-user'))->getRemainingTokens());
    }

    public function testAnExemptRequestConsumesNothing(): void
    {
        $limiter = $this->createLimiter();
        $valid = $this->createRequest('agent-user');

        for ($i = 0; $i < self::MAX_ATTEMPTS * 2; ++$i) {
            $limiter->consume($valid);
        }

        // NoLimiter reports PHP_INT_MAX rather than a decremented budget, so a successful
        // client can never walk itself into a lockout either.
        $this->assertSame(PHP_INT_MAX, $limiter->peek($valid)->getRemainingTokens());
    }

    /**
     * A request that never reached CheckPassportEvent - an authenticator that threw before
     * building a passport - carries no identifier at all and must not charge the bucket.
     */
    public function testARequestWithoutAnIdentifierIsExempt(): void
    {
        $limiter = $this->createLimiter();
        $request = Request::create('/pimcore-mcp/agent/documents', 'POST');
        $request->server->set('REMOTE_ADDR', self::CLIENT_IP);

        for ($i = 0; $i < self::MAX_ATTEMPTS; ++$i) {
            $limiter->consume($request);
        }

        $unknown = $this->createRequest(McpLoginRateLimiterInterface::UNKNOWN_CREDENTIAL_IDENTIFIER);
        $this->assertSame(self::MAX_ATTEMPTS, $limiter->peek($unknown)->getRemainingTokens());
    }

    public function testUnknownCredentialsAreBucketedPerClientIp(): void
    {
        $limiter = $this->createLimiter();

        for ($i = 0; $i < self::MAX_ATTEMPTS; ++$i) {
            $limiter->consume($this->createRequest(
                McpLoginRateLimiterInterface::UNKNOWN_CREDENTIAL_IDENTIFIER
            ));
        }

        $otherClient = $this->createRequest(
            McpLoginRateLimiterInterface::UNKNOWN_CREDENTIAL_IDENTIFIER,
            '198.51.100.7'
        );

        $this->assertSame(self::MAX_ATTEMPTS, $limiter->peek($otherClient)->getRemainingTokens());
    }

    private function createLimiter(): McpLoginRateLimiter
    {
        return new McpLoginRateLimiter(
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
    }

    private function createRequest(string $identifier, string $clientIp = self::CLIENT_IP): Request
    {
        $request = Request::create('/pimcore-mcp/agent/documents', 'POST');
        $request->server->set('REMOTE_ADDR', $clientIp);
        $request->attributes->set(SecurityRequestAttributes::LAST_USERNAME, $identifier);

        return $request;
    }
}
