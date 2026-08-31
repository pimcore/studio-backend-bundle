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
use Pimcore\Bundle\StudioBackendBundle\OAuth\Server\Entity\AccessTokenEntity;
use Psr\Http\Message\ServerRequestInterface;
use function is_string;

/**
 * Refresh-token grant that carries the resource binding across a refresh.
 *
 * Without this, refreshing would silently widen a token: the new access token would
 * carry no `aud` and would therefore be accepted at every protected resource, which
 * is exactly what audience binding exists to prevent.
 *
 * @internal
 */
final class ResourceRefreshTokenGrant extends RefreshTokenGrant
{
    /**
     * Captured while validating the old refresh token, valid only within one request.
     */
    private ?string $pendingResource = null;

    /**
     * @return array<string, mixed>
     *
     * @throws OAuthServerException
     */
    protected function validateOldRefreshToken(ServerRequestInterface $request, string $clientId): array
    {
        $refreshTokenData = parent::validateOldRefreshToken($request, $clientId);

        $this->pendingResource = is_string($refreshTokenData['resource'] ?? null)
            ? $refreshTokenData['resource']
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
