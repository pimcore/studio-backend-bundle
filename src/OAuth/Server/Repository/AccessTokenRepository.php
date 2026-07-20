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

use League\OAuth2\Server\Entities\AccessTokenEntityInterface;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Repositories\AccessTokenRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\Entity\OAuth\OAuthTokenRecord;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Server\Entity\AccessTokenEntity;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Server\Entity\ClientEntity;
use function ctype_digit;

/**
 * Mints access-token entities and records their identifiers so they can be
 * revoked. The tokens themselves are self-contained JWTs.
 *
 * @internal
 */
final class AccessTokenRepository implements AccessTokenRepositoryInterface
{
    public function __construct(
        private readonly ?string $issuer,
        private readonly TokenRecordStoreInterface $tokenRecordStore,
    ) {
    }

    /**
     * @param \League\OAuth2\Server\Entities\ScopeEntityInterface[] $scopes
     */
    public function getNewToken(
        ClientEntityInterface $clientEntity,
        array $scopes,
        ?string $userIdentifier = null,
    ): AccessTokenEntityInterface {
        $token = new AccessTokenEntity();
        $token->setClient($clientEntity);
        foreach ($scopes as $scope) {
            $token->addScope($scope);
        }

        // client_credentials has no end user; act as the client's configured
        // service user so the token resolves to a real Pimcore user downstream.
        $subject = $userIdentifier;
        if ($subject === null && $clientEntity instanceof ClientEntity && $clientEntity->getServiceUserId() !== null) {
            $subject = (string) $clientEntity->getServiceUserId();
        }
        if ($subject !== null) {
            $token->setUserIdentifier($subject);
        }

        $token->setIssuer($this->issuer);

        return $token;
    }

    public function persistNewAccessToken(AccessTokenEntityInterface $accessTokenEntity): void
    {
        $subject = $accessTokenEntity->getUserIdentifier();

        $this->tokenRecordStore->persist(
            $accessTokenEntity->getIdentifier(),
            OAuthTokenRecord::TYPE_ACCESS,
            $accessTokenEntity->getExpiryDateTime()->getTimestamp(),
            $subject !== null && ctype_digit($subject) ? (int) $subject : null,
            $accessTokenEntity->getClient()->getIdentifier(),
        );
    }

    public function revokeAccessToken(string $tokenId): void
    {
        $this->tokenRecordStore->revoke($tokenId);
    }

    public function isAccessTokenRevoked(string $tokenId): bool
    {
        return $this->tokenRecordStore->isRevoked($tokenId);
    }
}
