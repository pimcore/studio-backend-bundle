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

namespace Pimcore\Bundle\StudioBackendBundle\OAuth\Contract;

/**
 * Checks whether an access token (by its `jti`) has been revoked.
 *
 * JWT access tokens are self-contained, so revocation needs a server-side
 * lookup. Until the token store exists the default implementation reports
 * nothing as revoked.
 *
 * @internal
 */
interface TokenRevocationCheckerInterface
{
    public function isRevoked(string $tokenId): bool;
}
