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

namespace Pimcore\Bundle\StudioBackendBundle\OAuth\Server\Repository;

use League\OAuth2\Server\Entities\RefreshTokenEntityInterface;
use League\OAuth2\Server\Repositories\RefreshTokenRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\Entity\OAuth\OAuthTokenRecord;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Server\Entity\RefreshTokenEntity;

/**
 * Records issued refresh tokens for revocation and rotation reuse detection:
 * the refresh-token grant revokes the old token on use, so replaying it is
 * rejected.
 *
 * @internal
 */
final class RefreshTokenRepository implements RefreshTokenRepositoryInterface
{
    public function __construct(
        private readonly TokenRecordStoreInterface $tokenRecordStore,
    ) {
    }

    public function getNewRefreshToken(): RefreshTokenEntityInterface
    {
        return new RefreshTokenEntity();
    }

    public function persistNewRefreshToken(RefreshTokenEntityInterface $refreshTokenEntity): void
    {
        $accessToken = $refreshTokenEntity->getAccessToken();

        $this->tokenRecordStore->persist(
            $refreshTokenEntity->getIdentifier(),
            OAuthTokenRecord::TYPE_REFRESH,
            $refreshTokenEntity->getExpiryDateTime()->getTimestamp(),
            null,
            $accessToken->getClient()->getIdentifier(),
        );
    }

    public function revokeRefreshToken(string $tokenId): void
    {
        $this->tokenRecordStore->revoke($tokenId);
    }

    public function isRefreshTokenRevoked(string $tokenId): bool
    {
        return $this->tokenRecordStore->isRevoked($tokenId);
    }
}
