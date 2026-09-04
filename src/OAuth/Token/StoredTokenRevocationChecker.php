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
use Pimcore\Bundle\StudioBackendBundle\OAuth\Server\Repository\TokenRecordStoreInterface;

/**
 * Resource-server revocation check backed by the token record store, so a
 * revoked access token is rejected on its next request.
 *
 * @internal
 */
final readonly class StoredTokenRevocationChecker implements TokenRevocationCheckerInterface
{
    public function __construct(
        private TokenRecordStoreInterface $tokenRecordStore,
    ) {
    }

    public function isRevoked(string $tokenId): bool
    {
        return $this->tokenRecordStore->isRevoked($tokenId);
    }
}
