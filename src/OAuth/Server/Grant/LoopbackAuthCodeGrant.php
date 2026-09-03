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
use Pimcore\Bundle\StudioBackendBundle\OAuth\Dto\ProtectedResource;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Server\Entity\AccessTokenEntity;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Server\RedirectUri\LoopbackRedirectUriValidator;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Server\Repository\TokenRecordStoreInterface;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Server\RequestType\ResourceAuthorizationRequest;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Util\CanonicalUri;
use Psr\Http\Message\ServerRequestInterface;
use function array_filter;
use function array_map;
use function array_values;
use function implode;
use function in_array;
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

        // league validates the client and the redirect URI first, and the resource check
        // must not overtake it: an unknown client paired with a missing resource would
        // otherwise answer with the list of every registered resource on this server.
        $authorizationRequest = parent::validateAuthorizationRequest($request);
        $resource = $this->validatedResource($request);

        $resourceRequest = ResourceAuthorizationRequest::from($authorizationRequest, $resource);
        $resourceRequest->setScopes($this->narrowToResource($authorizationRequest, $resource));

        return $resourceRequest;
    }

    /**
     * RFC 8707: a token is downscoped to what the resource it names can actually process.
     * Narrowing here rather than when the token is issued is what makes the consent screen
     * honest, because the screen shows the scopes carried on this request.
     *
     * @return ScopeEntityInterface[]
     *
     * @throws OAuthServerException
     */
    private function narrowToResource(AuthorizationRequestInterface $request, string $resource): array
    {
        $supported = $this->resourceRegistry->get($resource)->scopesSupported ?? [];
        $requested = $request->getScopes();

        // A resource that declares no scopes constrains nothing, and a request that names
        // none has nothing to narrow. Neither is an error: both are reachable today, and
        // refusing them would turn working clients away over a token nobody checks.
        if ($supported === [] || $requested === []) {
            return $requested;
        }

        $narrowed = array_values(
            array_filter(
                $requested,
                static fn (ScopeEntityInterface $scope): bool => in_array(
                    $scope->getIdentifier(),
                    $supported,
                    true,
                ),
            )
        );

        if ($narrowed === []) {
            // Asked only for scopes this resource cannot process: the token would open
            // nothing, so say so instead of issuing it.
            throw OAuthServerException::invalidScope(
                implode(' ', array_map(
                    static fn (ScopeEntityInterface $scope): string => $scope->getIdentifier(),
                    $requested,
                )),
                $this->redirectUriFor($request),
            );
        }

        return $narrowed;
    }

    /**
     * The redirect league itself would have used for an `invalid_scope`, so a refusal
     * raised here reaches the client the same way rather than as a bare response.
     */
    private function redirectUriFor(AuthorizationRequestInterface $request): string
    {
        $state = $request->getState();

        return $this->makeRedirectUri(
            $request->getRedirectUri() ?? $this->getClientRedirectUri($request->getClient()),
            $state !== null ? ['state' => $state] : [],
        );
    }

    /**
     * RFC 8707: the request names the resource it wants the token for. Both an absent
     * and an unknown resource are refused rather than silently ignored, so a client
     * never believes it holds a narrowly-scoped token when it does not.
     *
     * @throws OAuthServerException
     */
    private function validatedResource(ServerRequestInterface $request): string
    {
        $resource = $this->getQueryStringParameter('resource', $request);
        if ($resource === null) {
            throw OAuthServerException::invalidRequest('resource', $this->missingResourceHint());
        }

        if (!$this->resourceRegistry->has($resource)) {
            throw OAuthServerException::invalidRequest(
                'resource',
                'The requested resource is not a known protected resource of this server. '
                . $this->knownResourcesHint()
            );
        }

        // Stamp the canonical form, not the client's spelling, so every consumer of the
        // `aud` claim can compare it without canonicalising first.
        return CanonicalUri::canonicalize($resource);
    }

    /**
     * An empty registry and a missing parameter are different problems with the same
     * symptom, so they get different messages: nothing can be issued at all until a
     * bundle or the configuration declares a protected resource.
     */
    private function missingResourceHint(): string
    {
        if ($this->resourceRegistry->all() === []) {
            return 'This authorization server has no protected resources configured, '
                . 'so no token can be issued. Declare one under '
                . 'pimcore_studio_backend.oauth.resources, or install a bundle that registers its own.';
        }

        return 'The resource the token is requested for must be named (RFC 8707). '
            . $this->knownResourcesHint();
    }

    /**
     * The resource URIs are already public: each one publishes its own RFC 9728
     * metadata document, and a 401 from the endpoint points at it. Naming them here
     * only saves the client a discovery round trip it is entitled to make anyway.
     */
    private function knownResourcesHint(): string
    {
        $known = array_map(
            static fn (ProtectedResource $resource): string => $resource->canonicalUri,
            $this->resourceRegistry->all(),
        );

        return 'Known resources: ' . implode(', ', $known) . '.';
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
