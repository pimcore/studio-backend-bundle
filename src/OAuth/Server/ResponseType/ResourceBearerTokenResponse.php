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

namespace Pimcore\Bundle\StudioBackendBundle\OAuth\Server\ResponseType;

use League\OAuth2\Server\ResponseTypes\BearerTokenResponse;
use LogicException;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Server\Entity\AccessTokenEntity;
use Psr\Http\Message\ResponseInterface;
use function array_merge;
use function json_encode;
use function time;

/**
 * Bearer response that records the token's resource in the refresh-token payload.
 *
 * Without it a refresh would silently widen the token: the refreshed access token
 * would carry no `aud` and would therefore be accepted at every protected resource,
 * defeating the binding the original grant established.
 *
 * league builds the refresh payload inline with no extension point, so
 * `generateHttpResponse()` is mirrored here with one field added. Revisit on a league
 * major upgrade.
 *
 * @internal
 */
final class ResourceBearerTokenResponse extends BearerTokenResponse
{
    public function generateHttpResponse(ResponseInterface $response): ResponseInterface
    {
        $expireDateTime = $this->accessToken->getExpiryDateTime()->getTimestamp();

        $responseParams = [
            'token_type' => 'Bearer',
            'expires_in' => $expireDateTime - time(),
            'access_token' => $this->accessToken->toString(),
        ];

        if (isset($this->refreshToken)) {
            $payload = [
                'client_id' => $this->accessToken->getClient()->getIdentifier(),
                'refresh_token_id' => $this->refreshToken->getIdentifier(),
                'access_token_id' => $this->accessToken->getIdentifier(),
                'scopes' => $this->accessToken->getScopes(),
                'user_id' => $this->accessToken->getUserIdentifier(),
                'expire_time' => $this->refreshToken->getExpiryDateTime()->getTimestamp(),
                'resource' => $this->accessToken instanceof AccessTokenEntity
                    ? $this->accessToken->getAudience()
                    : null,
            ];

            $refreshTokenPayload = json_encode($payload);
            if ($refreshTokenPayload === false) {
                throw new LogicException('Error encountered JSON encoding the refresh token payload');
            }

            $responseParams['refresh_token'] = $this->encrypt($refreshTokenPayload);
        }

        $encoded = json_encode(array_merge($this->getExtraParams($this->accessToken), $responseParams));
        if ($encoded === false) {
            throw new LogicException('Error encountered JSON encoding response parameters');
        }

        $response = $response
            ->withStatus(200)
            ->withHeader('pragma', 'no-cache')
            ->withHeader('cache-control', 'no-store')
            ->withHeader('content-type', 'application/json; charset=UTF-8');

        $response->getBody()->write($encoded);

        return $response;
    }
}
