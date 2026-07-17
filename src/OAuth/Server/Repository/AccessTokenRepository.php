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
use Pimcore\Bundle\StudioBackendBundle\OAuth\Server\Entity\AccessTokenEntity;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Server\Entity\ClientEntity;

/**
 * Mints access-token entities. Tokens are self-contained JWTs, so there is no
 * persistence yet; a store for revocation and refresh-token reuse detection is
 * added alongside the refresh-token grant.
 *
 * @internal
 */
final class AccessTokenRepository implements AccessTokenRepositoryInterface
{
    public function __construct(
        private readonly ?string $issuer,
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
        // No-op: stateless JWTs. Persistence lands with the revocation store.
    }

    public function revokeAccessToken(string $tokenId): void
    {
        // No-op until the revocation store exists.
    }

    public function isAccessTokenRevoked(string $tokenId): bool
    {
        return false;
    }
}
