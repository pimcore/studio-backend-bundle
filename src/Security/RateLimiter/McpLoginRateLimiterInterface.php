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
 * resolved to no user at all. A credential that resolves to a user is answered with no
 * limiters, so it can neither be blocked nor charged on its own account - that is a property
 * of the keying, not a threshold.
 *
 * The guarantee is per credential, not per request. AuthenticatorManager keeps running
 * authenticators after one has already succeeded, so a request that presents a *second*,
 * unrecognised credential alongside a good one is still judged on the unrecognised one and
 * can be answered with the throttled response. That is pre-existing chain behaviour - the
 * same request already lost to a terminal 401 - and the status code is what changes here.
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
     * The leading NUL keeps it out of the namespace of real identifiers: PatAuthenticator
     * takes the username straight from the configured token map, so a plain "__invalid__"
     * would collide with a Pimcore user of that name and drag their valid PAT into the
     * guess bucket. No username can contain a NUL, so no recognised credential can carry it.
     */
    public const string UNKNOWN_CREDENTIAL_IDENTIFIER = "\0__invalid__";
}
