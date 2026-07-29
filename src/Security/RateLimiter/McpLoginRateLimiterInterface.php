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

use Symfony\Component\HttpFoundation\RateLimiter\PeekableRequestRateLimiterInterface;

/**
 * Throttles guesses at MCP bearer credentials.
 *
 * Wired into the MCP firewall as `login_throttling.limiter`, replacing Symfony's
 * DefaultLoginRateLimiter. That default keys one tier on identifier+IP and derives a second
 * one on IP alone; the per-IP tier is charged by every client on an address and peeked by
 * every client on it, so a guesser can push a valid credential into a 429.
 *
 * This limiter has a single tier and only ever hands out a bucket for a credential that
 * resolved to no user at all. A credential that resolves to a user - which is every
 * credential that can go on to authenticate - is answered with no limiters, so it cannot be
 * blocked and cannot be charged. Rejecting a successful authentication is therefore not a
 * matter of tuning: no bucket exists to reject it from.
 *
 * @internal
 */
interface McpLoginRateLimiterInterface extends PeekableRequestRateLimiterInterface
{
    /**
     * User identifier that PatAuthenticator puts on the UserBadge when a bearer token matches
     * no configured PAT. It is deliberately constant: DefaultLoginRateLimiter-style keying on
     * the credential itself would hand every guess a fresh bucket, so a sweep would never fill
     * one. With a constant identifier the bucket collapses to "unknown credentials from this
     * IP", which is the only shape that bounds a sweep.
     *
     * Nothing that authenticates successfully ever carries it, so it can never lock anyone out.
     */
    public const string UNKNOWN_CREDENTIAL_IDENTIFIER = '__invalid__';
}
