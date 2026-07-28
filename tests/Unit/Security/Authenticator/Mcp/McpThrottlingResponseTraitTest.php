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
use Pimcore\Bundle\StudioBackendBundle\Security\Authenticator\Mcp\McpThrottlingResponseTrait;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\TooManyLoginAttemptsAuthenticationException;

final class McpThrottlingResponseTraitTest extends Unit
{
    public function testReturnsNullForOrdinaryAuthenticationFailures(): void
    {
        // Returning null preserves the existing fall-through: AuthenticatorManager keeps
        // trying the remaining authenticators on the firewall.
        $this->assertNull($this->makeSubject()->call(new AuthenticationException('nope')));
    }

    public function testMapsThrottlingToA429WithRetryAfterInSeconds(): void
    {
        $response = $this->makeSubject()->call(new TooManyLoginAttemptsAuthenticationException(5));

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(429, $response->getStatusCode());
        // The exception reports minutes; Retry-After is defined in seconds.
        $this->assertSame('300', $response->headers->get('Retry-After'));
    }

    public function testFallsBackToAMinimumRetryAfterWhenNoThresholdIsGiven(): void
    {
        $response = $this->makeSubject()->call(new TooManyLoginAttemptsAuthenticationException());

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(429, $response->getStatusCode());
        $this->assertSame('60', $response->headers->get('Retry-After'));
    }

    private function makeSubject(): object
    {
        return new class() {
            use McpThrottlingResponseTrait;

            public function call(AuthenticationException $exception): ?JsonResponse
            {
                return $this->throttlingResponse($exception);
            }
        };
    }
}
