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

namespace Pimcore\Bundle\StudioBackendBundle\OAuth\Server\Grant;

use DateInterval;
use League\OAuth2\Server\Entities\AccessTokenEntityInterface;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Exception\OAuthServerException;
use League\OAuth2\Server\Exception\UniqueTokenIdentifierConstraintViolationException;
use League\OAuth2\Server\Grant\RefreshTokenGrant;
use League\OAuth2\Server\Repositories\RefreshTokenRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Server\Entity\AccessTokenEntity;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Server\Repository\TokenRecordStoreInterface;
use Psr\Http\Message\ServerRequestInterface;
use function is_string;

/**
 * Refresh-token grant that carries the resource binding across a refresh.
 *
 * Without this, refreshing would break a token: the new access token would carry no
 * `aud`, and the validator refuses an audience-less token at every protected resource,
 * so a refresh would hand back a credential that opens nothing.
 *
 * The binding is read from the token record rather than the refresh payload, because
 * league builds that payload itself and offers no extension point.
 *
 * @internal
 */
final class ResourceRefreshTokenGrant extends RefreshTokenGrant
{
    private TokenRecordStoreInterface $tokenRecordStore;

    /**
     * Captured while validating the old refresh token, valid only within one request.
     */
    private ?string $pendingResource = null;

    public function __construct(
        RefreshTokenRepositoryInterface $refreshTokenRepository,
        TokenRecordStoreInterface $tokenRecordStore,
    ) {
        parent::__construct($refreshTokenRepository);

        $this->tokenRecordStore = $tokenRecordStore;
    }

    /**
     * @return array<string, mixed>
     *
     * @throws OAuthServerException
     */
    protected function validateOldRefreshToken(ServerRequestInterface $request, string $clientId): array
    {
        $refreshTokenData = parent::validateOldRefreshToken($request, $clientId);

        $tokenId = $refreshTokenData['refresh_token_id'] ?? null;
        $this->pendingResource = is_string($tokenId)
            ? $this->tokenRecordStore->resourceFor($tokenId)
            : null;

        return $refreshTokenData;
    }

    /**
     * @param array<int, mixed> $scopes
     *
     * @throws OAuthServerException
     * @throws UniqueTokenIdentifierConstraintViolationException
     */
    protected function issueAccessToken(
        DateInterval $accessTokenTTL,
        ClientEntityInterface $client,
        ?string $userIdentifier,
        array $scopes = []
    ): AccessTokenEntityInterface {
        $accessToken = parent::issueAccessToken($accessTokenTTL, $client, $userIdentifier, $scopes);

        if ($accessToken instanceof AccessTokenEntity) {
            $accessToken->setAudience($this->pendingResource);
        }

        return $accessToken;
    }
}
