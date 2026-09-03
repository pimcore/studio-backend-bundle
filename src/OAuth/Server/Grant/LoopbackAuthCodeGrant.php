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
use DateTimeImmutable;
use Exception;
use League\OAuth2\Server\Entities\AccessTokenEntityInterface;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Entities\ScopeEntityInterface;
use League\OAuth2\Server\Entities\UserEntityInterface;
use League\OAuth2\Server\Exception\OAuthServerException;
use League\OAuth2\Server\Exception\UniqueTokenIdentifierConstraintViolationException;
use League\OAuth2\Server\Grant\AuthCodeGrant;
use League\OAuth2\Server\Repositories\AuthCodeRepositoryInterface;
use League\OAuth2\Server\Repositories\RefreshTokenRepositoryInterface;
use League\OAuth2\Server\RequestEvent;
use League\OAuth2\Server\RequestTypes\AuthorizationRequestInterface;
use League\OAuth2\Server\ResponseTypes\RedirectResponse;
use League\OAuth2\Server\ResponseTypes\ResponseTypeInterface;
use LogicException;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Contract\ResourceRegistryInterface;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Server\Entity\AccessTokenEntity;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Server\RedirectUri\LoopbackRedirectUriValidator;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Server\RequestType\ResourceAuthorizationRequest;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Util\CanonicalUri;
use Psr\Http\Message\ServerRequestInterface;
use function is_array;
use function is_string;
use function json_decode;
use function json_encode;

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
    private function validatedResource(ServerRequestInterface $request): ?string
    {
        $resource = $this->getQueryStringParameter('resource', $request);
        if ($resource === null) {
            return null;
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
     * Mirrors the league implementation, adding the resource to the authorization code
     * payload. league offers no hook for extra payload fields, so the method body is
     * duplicated; revisit on a league major upgrade.
     */
    public function completeAuthorizationRequest(
        AuthorizationRequestInterface $authorizationRequest
    ): ResponseTypeInterface {
        if (!$authorizationRequest->getUser() instanceof UserEntityInterface) {
            throw new LogicException('An instance of UserEntityInterface should be set on the AuthorizationRequest');
        }

        $finalRedirectUri = $authorizationRequest->getRedirectUri()
            ?? $this->getClientRedirectUri($authorizationRequest->getClient());

        if ($authorizationRequest->isAuthorizationApproved() !== true) {
            throw OAuthServerException::accessDenied(
                'The user denied the request',
                $this->makeRedirectUri($finalRedirectUri, ['state' => $authorizationRequest->getState()])
            );
        }

        $authCode = $this->issueAuthCode(
            $this->ownAuthCodeTTL,
            $authorizationRequest->getClient(),
            $authorizationRequest->getUser()->getIdentifier(),
            $authorizationRequest->getRedirectUri(),
            $authorizationRequest->getScopes()
        );

        $payload = [
            'client_id' => $authCode->getClient()->getIdentifier(),
            'redirect_uri' => $authCode->getRedirectUri(),
            'auth_code_id' => $authCode->getIdentifier(),
            'scopes' => $authCode->getScopes(),
            'user_id' => $authCode->getUserIdentifier(),
            'expire_time' => (new DateTimeImmutable())->add($this->ownAuthCodeTTL)->getTimestamp(),
            'code_challenge' => $authorizationRequest->getCodeChallenge(),
            'code_challenge_method' => $authorizationRequest->getCodeChallengeMethod(),
            'resource' => $authorizationRequest instanceof ResourceAuthorizationRequest
                ? $authorizationRequest->getResource()
                : null,
        ];

        $jsonPayload = json_encode($payload);
        if ($jsonPayload === false) {
            throw new LogicException('An error was encountered when JSON encoding the authorization request response');
        }

        $response = new RedirectResponse();
        $response->setRedirectUri(
            $this->makeRedirectUri($finalRedirectUri, [
                'code' => $this->encrypt($jsonPayload),
                'state' => $authorizationRequest->getState(),
            ])
        );

        return $response;
    }

    /**
     * league keeps its own authorization-code validation private, so there is no hook
     * on the decrypted payload. The client check is the first thing the token request
     * does and it does receive the request, so the code is decrypted once more here to
     * recover the resource it was issued for. The payload is authenticated by league's
     * own encryption, so trusting it is no weaker than league trusting it.
     *
     * @throws OAuthServerException
     */
    protected function validateClient(ServerRequestInterface $request): ClientEntityInterface
    {
        $client = parent::validateClient($request);

        $this->pendingResource = null;
        $encryptedCode = $this->getRequestParameter('code', $request);

        if (is_string($encryptedCode)) {
            try {
                $payload = json_decode($this->decrypt($encryptedCode), true);
            } catch (Exception) {
                return $client;
            }

            if (is_array($payload) && is_string($payload['resource'] ?? null)) {
                $this->pendingResource = $payload['resource'];
            }
        }

        return $client;
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

    /**
     * league keeps its own $authCodeTTL private, so the mirrored
     * {@see self::completeAuthorizationRequest()} needs its own copy.
     */
    private readonly DateInterval $ownAuthCodeTTL;

    public function __construct(
        AuthCodeRepositoryInterface $authCodeRepository,
        RefreshTokenRepositoryInterface $refreshTokenRepository,
        DateInterval $authCodeTTL,
        private readonly bool $allowLocalhostLoopback,
        private readonly ResourceRegistryInterface $resourceRegistry,
    ) {
        parent::__construct($authCodeRepository, $refreshTokenRepository, $authCodeTTL);

        $this->ownAuthCodeTTL = $authCodeTTL;
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
