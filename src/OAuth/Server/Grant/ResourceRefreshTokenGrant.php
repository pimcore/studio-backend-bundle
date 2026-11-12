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
    private const string UNBOUND_REFRESH_TOKEN_HINT = 'The refresh token is not bound to a protected resource '
        . 'of this server. Start a new authorization request.';

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
     * Refuses a refresh token whose binding cannot be recovered, rather than refreshing it
     * into an audience-less token. That happens whenever the record is gone - the store was
     * wiped, restored from an older backup, or the expired-record cleanup removed it - and
     * league accepts such a token regardless, because its revocation check reads an unknown
     * identifier as "not revoked".
     *
     * Minting anyway hands the client HTTP 200 and a credential the validator refuses at
     * every protected resource, so the failure surfaces later as an unexplained 401 loop at
     * the resource instead of here. `invalid_grant` is the answer a client knows how to act
     * on: start a new authorization.
     *
     * Checked here rather than at {@see self::issueAccessToken()} because league revokes the
     * old access and refresh tokens in between: refusing first leaves the client's existing
     * credentials intact for a request that was refused.
     *
     * @return array<string, mixed>
     *
     * @throws OAuthServerException
     */
    protected function validateOldRefreshToken(ServerRequestInterface $request, string $clientId): array
    {
        $refreshTokenData = parent::validateOldRefreshToken($request, $clientId);

        $tokenId = $refreshTokenData['refresh_token_id'] ?? null;
        $resource = is_string($tokenId) ? $this->tokenRecordStore->resourceFor($tokenId) : null;

        if ($resource === null) {
            throw OAuthServerException::invalidRefreshToken(self::UNBOUND_REFRESH_TOKEN_HINT);
        }

        $this->pendingResource = $resource;

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
