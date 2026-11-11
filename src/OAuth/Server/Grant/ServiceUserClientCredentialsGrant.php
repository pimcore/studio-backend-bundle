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
use League\OAuth2\Server\Entities\ScopeEntityInterface;
use League\OAuth2\Server\Exception\OAuthServerException;
use League\OAuth2\Server\Exception\UniqueTokenIdentifierConstraintViolationException;
use League\OAuth2\Server\Grant\ClientCredentialsGrant;
use League\OAuth2\Server\RequestEvent;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Contract\IdentityResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Contract\ResourceRegistryInterface;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Server\Entity\AccessTokenEntity;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Server\Entity\ClientEntity;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Server\Entity\ScopeEntity;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Util\CanonicalUri;
use Pimcore\Model\User;
use Psr\Http\Message\ServerRequestInterface;
use function array_diff;
use function array_filter;
use function array_map;
use function array_values;
use function implode;
use function in_array;
use function is_string;

/**
 * client_credentials for confidential clients bound one to one to a service user.
 *
 * league's grant issues a token for "the client". Here the client is only the credential
 * of a service user, so a token is issued only when the bound service user resolves as an
 * active service user, with that user as subject (see AccessTokenRepository::getNewToken()),
 * for the protected resource the request names (RFC 8707, as the authorization-code grant
 * requires) and with scopes within the client's registration. league never issues a
 * refresh token for this grant.
 *
 * Enabled only while pimcore_studio_backend.oauth.client_credentials.enabled is true; see
 * AuthorizationServerFactory::create().
 *
 * @internal
 */
final class ServiceUserClientCredentialsGrant extends ClientCredentialsGrant
{
    private const string UNBOUND_HINT = 'The client is not bound to a service user, or the request names no resource.';

    /**
     * The resource of the request being answered, captured while validating the client.
     * Valid only within one token request.
     */
    private ?string $pendingResource = null;

    public function __construct(
        private readonly ResourceRegistryInterface $resourceRegistry,
        private readonly IdentityResolverInterface $identityResolver,
    ) {
    }

    /**
     * Refuses every client that is not bound to an active service user, then the request
     * if it names no known resource. The client is authenticated before the resource is
     * looked at, so an unauthenticated caller learns nothing about the resources.
     *
     * @throws OAuthServerException
     */
    protected function validateClient(ServerRequestInterface $request): ClientEntityInterface
    {
        $this->pendingResource = null;

        $client = parent::validateClient($request);

        $serviceUserId = $client instanceof ClientEntity ? $client->getServiceUserId() : null;
        if ($serviceUserId === null || !$client->isConfidential() || !$this->isActiveServiceUser($serviceUserId)) {
            $this->getEmitter()->emit(new RequestEvent(RequestEvent::CLIENT_AUTHENTICATION_FAILED, $request));

            throw OAuthServerException::invalidClient($request);
        }

        $this->pendingResource = $this->validatedResource($request);

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
        $resource = $this->pendingResource;
        // validateClient() has already refused anything else; this only keeps a token from
        // ever being minted without a service user or a resource.
        if ($resource === null || !$client instanceof ClientEntity || $client->getServiceUserId() === null) {
            throw OAuthServerException::invalidGrant(self::UNBOUND_HINT);
        }

        $accessToken = parent::issueAccessToken(
            $accessTokenTTL,
            $client,
            (string) $client->getServiceUserId(),
            $this->grantedScopes($client, $scopes, $resource),
        );

        if ($accessToken instanceof AccessTokenEntity) {
            $accessToken->setAudience($resource);
        }

        return $accessToken;
    }

    private function isActiveServiceUser(int $serviceUserId): bool
    {
        // The resolver applies the same validity check as every protected resource does
        // (active, existing); the type check keeps a binding to a human user from ever
        // minting a token for that human.
        $user = $this->identityResolver->resolve((string) $serviceUserId);

        return $user instanceof User && $user->isServiceUser();
    }

    /**
     * Scopes within the registration: none requested means the registered ones, and a
     * request beyond the registration is refused rather than silently narrowed. The result
     * is then narrowed to what the resource can process, as for the authorization code.
     *
     * @param ScopeEntityInterface[] $requested
     *
     * @return ScopeEntityInterface[]
     *
     * @throws OAuthServerException
     */
    private function grantedScopes(ClientEntity $client, array $requested, string $resource): array
    {
        $registered = $client->getRegisteredScopes() ?? [];
        $requestedIds = array_map(
            static fn (ScopeEntityInterface $scope): string => $scope->getIdentifier(),
            $requested,
        );

        $outside = array_diff($requestedIds, $registered);
        if ($outside !== []) {
            throw OAuthServerException::invalidScope(implode(' ', $outside));
        }

        $granted = $requestedIds === [] ? $registered : $requestedIds;

        $supported = $this->resourceRegistry->get($resource)->scopesSupported ?? [];
        if ($supported !== []) {
            $narrowed = array_values(
                array_filter($granted, static fn (string $scope): bool => in_array($scope, $supported, true))
            );
            if ($granted !== [] && $narrowed === []) {
                throw OAuthServerException::invalidScope(implode(' ', $granted));
            }
            $granted = $narrowed;
        }

        return array_map(static fn (string $scope): ScopeEntity => new ScopeEntity($scope), $granted);
    }

    /**
     * RFC 8707: the request names the resource the token is for. An absent and an unknown
     * resource are both refused, because the validator refuses a token without `aud` at
     * every protected resource.
     *
     * @throws OAuthServerException
     */
    private function validatedResource(ServerRequestInterface $request): string
    {
        $resource = $this->getRequestParameter('resource', $request);
        if (!is_string($resource)) {
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

        return CanonicalUri::canonicalize($resource);
    }
}
