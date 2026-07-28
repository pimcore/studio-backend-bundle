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

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\TooManyLoginAttemptsAuthenticationException;
use function is_numeric;

/**
 * Maps login throttling to a machine-readable 429.
 *
 * `login_throttling` raises TooManyLoginAttemptsAuthenticationException, an ordinary
 * AuthenticationException, which the firewall entry point would otherwise render as a
 * bare 401 - telling an MCP client its credential is wrong when the truth is "back off".
 * MCP clients are programs that act on Retry-After, so the real status matters.
 *
 * @internal
 */
trait McpThrottlingResponseTrait
{
    private const int SECONDS_PER_MINUTE = 60;

    /**
     * Used when the exception carries no threshold. Deliberately a separate constant
     * from SECONDS_PER_MINUTE despite the equal value - they mean different things.
     */
    private const int FALLBACK_RETRY_AFTER_SECONDS = 60;

    private function throttlingResponse(AuthenticationException $exception): ?JsonResponse
    {
        if (!$exception instanceof TooManyLoginAttemptsAuthenticationException) {
            // Not throttling. Returning null preserves the fall-through to the next
            // authenticator on this firewall.
            return null;
        }

        // getMessageData() reports the threshold in minutes, and it may be null.
        $minutes = $exception->getMessageData()['%minutes%'] ?? null;
        $retryAfter = is_numeric($minutes)
            ? (int) $minutes * self::SECONDS_PER_MINUTE
            : self::FALLBACK_RETRY_AFTER_SECONDS;

        $response = new JsonResponse(
            ['error' => 'Too many failed authentication attempts. Please try again later.'],
            JsonResponse::HTTP_TOO_MANY_REQUESTS
        );
        $response->headers->set('Retry-After', (string) $retryAfter);

        return $response;
    }
}
