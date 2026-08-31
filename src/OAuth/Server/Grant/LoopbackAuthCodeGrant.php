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
use Exception;
use League\OAuth2\Server\Entities\AccessTokenEntityInterface;
use League\OAuth2\Server\Entities\AuthCodeEntityInterface;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Entities\ScopeEntityInterface;
use League\OAuth2\Server\Exception\OAuthServerException;
use League\OAuth2\Server\Exception\UniqueTokenIdentifierConstraintViolationException;
use League\OAuth2\Server\Grant\AuthCodeGrant;
use League\OAuth2\Server\Repositories\AuthCodeRepositoryInterface;
use League\OAuth2\Server\Repositories\RefreshTokenRepositoryInterface;
use League\OAuth2\Server\RequestEvent;
use League\OAuth2\Server\RequestTypes\AuthorizationRequestInterface;
use League\OAuth2\Server\ResponseTypes\ResponseTypeInterface;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Contract\ResourceRegistryInterface;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Server\Entity\AccessTokenEntity;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Server\RedirectUri\LoopbackRedirectUriValidator;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Server\Repository\TokenRecordStoreInterface;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Server\RequestType\ResourceAuthorizationRequest;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Util\CanonicalUri;
use Psr\Http\Message\ServerRequestInterface;
use function is_array;
use function is_string;
use function json_decode;

/**
 * Authorization-code grant that validates redirect URIs with the RFC 8252
 * loopback exception, optionally extended to `localhost` (see
 * {@see LoopbackRedirectUriValidator}), and requires the S256 PKCE
 * transformation. The redirect-URI and PKCE-method validation are overridden;
 * all other behaviour is the league grant's.
 *
 * @internal
 */
final class LoopbackAuthCodeGrant extends AuthCodeGrant
{
    private const string REQUIRED_CODE_CHALLENGE_METHOD = 'S256';

    /**
     * OAuth 2.1 and our advertised metadata support only the S256 PKCE
     * transformation, but the league grant still accepts `plain` (and defaults
     * to it when the method is omitted). Reject anything but S256 before
     * delegating, so the server never issues a code protected by a plain
     * challenge.
     */
    public function validateAuthorizationRequest(ServerRequestInterface $request): AuthorizationRequestInterface
    {
        if ($this->getQueryStringParameter('code_challenge', $request) !== null) {
            $method = $this->getQueryStringParameter('code_challenge_method', $request, 'plain');

            if ($method !== self::REQUIRED_CODE_CHALLENGE_METHOD) {
                throw OAuthServerException::invalidRequest(
                    'code_challenge_method',
                    'Only the S256 code challenge method is supported.'
                );
            }
        }

        return ResourceAuthorizationRequest::from(
            parent::validateAuthorizationRequest($request),
            $this->validatedResource($request),
        );
    }

    /**
     * RFC 8707: the client may name the resource it wants the token for. An unknown
     * resource is refused rather than silently ignored, so a client never believes it
     * holds a narrowly-scoped token when it does not.
     *
     * @throws OAuthServerException
     */
    private function validatedResource(ServerRequestInterface $request): string
    {
        $resource = $this->getQueryStringParameter('resource', $request);
        if ($resource === null) {
            throw OAuthServerException::invalidRequest(
                'resource',
                'The resource the token is requested for must be named (RFC 8707).'
            );
        }

        if (!$this->resourceRegistry->has($resource)) {
            throw OAuthServerException::invalidRequest(
                'resource',
                'The requested resource is not a known protected resource of this server.'
            );
        }

        // Stamp the canonical form, not the client's spelling, so every consumer of the
        // `aud` claim can compare it without canonicalising first.
        return CanonicalUri::canonicalize($resource);
    }

    /**
     * Captures the resource so {@see self::issueAuthCode()} can record it against the
     * code that is about to be issued. league builds the code payload itself and offers
     * no hook for extra fields, so the binding is persisted alongside the token record
     * instead of being smuggled into that payload.
     */
    public function completeAuthorizationRequest(
        AuthorizationRequestInterface $authorizationRequest
    ): ResponseTypeInterface {
        $this->pendingResource = $authorizationRequest instanceof ResourceAuthorizationRequest
            ? $authorizationRequest->getResource()
            : null;

        return parent::completeAuthorizationRequest($authorizationRequest);
    }

    /**
     * @param ScopeEntityInterface[] $scopes
     *
     * @throws OAuthServerException
     * @throws UniqueTokenIdentifierConstraintViolationException
     */
    protected function issueAuthCode(
        DateInterval $authCodeTTL,
        ClientEntityInterface $client,
        string $userIdentifier,
        ?string $redirectUri,
        array $scopes = []
    ): AuthCodeEntityInterface {
        $authCode = parent::issueAuthCode($authCodeTTL, $client, $userIdentifier, $redirectUri, $scopes);

        $this->tokenRecordStore->bindResource($authCode->getIdentifier(), $this->pendingResource);

        return $authCode;
    }

    /**
     * league validates and decrypts the authorization code inside a private method, so
     * there is no hook on the decrypted payload. The token request entry point is the
     * right layer to recover the binding: the code id is enough to look the resource up,
     * and the record was written when the code was issued.
     *
     * @throws OAuthServerException
     */
    public function respondToAccessTokenRequest(
        ServerRequestInterface $request,
        ResponseTypeInterface $responseType,
        DateInterval $accessTokenTTL
    ): ResponseTypeInterface {
        $this->pendingResource = $this->boundResource($request);

        return parent::respondToAccessTokenRequest($request, $responseType, $accessTokenTTL);
    }

    private function boundResource(ServerRequestInterface $request): ?string
    {
        $encryptedCode = $this->getRequestParameter('code', $request);
        if (!is_string($encryptedCode)) {
            return null;
        }

        try {
            $payload = json_decode($this->decrypt($encryptedCode), true);
        } catch (Exception) {
            return null;
        }

        $codeId = is_array($payload) ? ($payload['auth_code_id'] ?? null) : null;

        return is_string($codeId) ? $this->tokenRecordStore->resourceFor($codeId) : null;
    }

    /**
     * @param ScopeEntityInterface[] $scopes
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

    /**
     * The resource the authorization code was issued for, captured while validating
     * that code so {@see self::issueAccessToken()} can stamp it onto the token. Valid
     * only within a single token request.
     */
    private ?string $pendingResource = null;

    public function __construct(
        AuthCodeRepositoryInterface $authCodeRepository,
        RefreshTokenRepositoryInterface $refreshTokenRepository,
        DateInterval $authCodeTTL,
        private readonly bool $allowLocalhostLoopback,
        private readonly ResourceRegistryInterface $resourceRegistry,
        private readonly TokenRecordStoreInterface $tokenRecordStore,
    ) {
        parent::__construct($authCodeRepository, $refreshTokenRepository, $authCodeTTL);

    }

    protected function validateRedirectUri(
        string $redirectUri,
        ClientEntityInterface $client,
        ServerRequestInterface $request,
    ): void {
        $validator = new LoopbackRedirectUriValidator($client->getRedirectUri(), $this->allowLocalhostLoopback);

        if (!$validator->validateRedirectUri($redirectUri)) {
            $this->getEmitter()->emit(new RequestEvent(RequestEvent::CLIENT_AUTHENTICATION_FAILED, $request));

            throw OAuthServerException::invalidClient($request);
        }
    }
}
