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

namespace Pimcore\Bundle\StudioBackendBundle\Mercure\Service;

use DateTimeImmutable;

/**
 * @internal
 */
interface AccessTokenFactoryInterface
{
    /**
     * Creates a token accepted by hubs of both protocols: Mercure 1.0 access token claims
     * plus the 0.x "mercure" claim. Each hub ignores the claims of the other protocol.
     *
     * @param string[] $subscribe
     * @param string[] $publish
     * @param string[] $audience
     */
    public function create(
        array $subscribe,
        array $publish,
        string $subject,
        array $audience,
        DateTimeImmutable $expiresAt
    ): string;

    /**
     * Re-mints a token of this factory as a 0.x token: only the "mercure" claim, issuer and expiry.
     * 1.x hubs accept nothing else in the legacy cookie. Any other token is returned unchanged.
     */
    public function toLegacy(string $accessToken): string;
}
