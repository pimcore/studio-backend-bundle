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

namespace Pimcore\Bundle\StudioBackendBundle\Security\RateLimiter;

use Symfony\Component\HttpFoundation\RateLimiter\AbstractRequestRateLimiter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\RateLimiter\LimiterInterface;
use Symfony\Component\RateLimiter\RateLimiterFactoryInterface;
use Symfony\Component\Security\Http\SecurityRequestAttributes;

/**
 * @internal
 */
final class McpLoginRateLimiter extends AbstractRequestRateLimiter implements McpLoginRateLimiterInterface
{
    private const string UNKNOWN_CLIENT = 'unknown';

    public function __construct(
        private readonly RateLimiterFactoryInterface $limiterFactory,
    ) {
    }

    /**
     * LoginThrottlingListener::checkPassport() writes the passport's user identifier to
     * LAST_USERNAME before it peeks, and the identifier is still on the request when
     * onFailedLogin() charges. Reading it here is therefore what decides both whether a
     * request can be blocked and whether its failure counts.
     *
     * Returning an empty list is a per-request exemption: AbstractRequestRateLimiter
     * substitutes a NoLimiter, which reports PHP_INT_MAX remaining tokens and consumes
     * nothing.
     *
     * @return LimiterInterface[]
     */
    protected function getLimiters(Request $request): array
    {
        $identifier = $request->attributes->get(SecurityRequestAttributes::LAST_USERNAME);

        if ($identifier !== self::UNKNOWN_CREDENTIAL_IDENTIFIER) {
            return [];
        }

        return [$this->limiterFactory->create($request->getClientIp() ?? self::UNKNOWN_CLIENT)];
    }
}
