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

namespace Pimcore\Bundle\StudioBackendBundle\OAuth\Token;

use Pimcore\Bundle\StudioBackendBundle\OAuth\Contract\TokenRevocationCheckerInterface;

/**
 * Default revocation checker: treats every token as not revoked. Replaced by a
 * store-backed implementation once token persistence exists.
 *
 * @internal
 */
final class NullTokenRevocationChecker implements TokenRevocationCheckerInterface
{
    public function isRevoked(string $tokenId): bool
    {
        return false;
    }
}
