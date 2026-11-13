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

use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Repositories\ClientRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Contract\ClientMetadataResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Server\Entity\ClientEntity;
use function hash;
use function hash_equals;
use function str_starts_with;

/**
 * Serves clients from three sources, in precedence order: pre-registered public
 * clients declared in bundle configuration (authoritative, and available even
 * when the self-registration mechanisms are off); clients identified by a
 * URL-form client_id resolved on demand from a Client ID Metadata Document
 * (CIMD); and clients created at runtime via Dynamic Client Registration
 * (looked up through the store).
 *
 * Every source is public: clients authenticate a logged-in user via the
 * authorization_code + PKCE flow and carry no secret (a confidential dynamic
 * client is the only exception, validated at the token endpoint). There is no
 * service/machine client — machine access uses the PAT authenticator instead.
 *
 * Only dynamically registered clients declare `grant_types` and `scope`. Config-declared
 * and CIMD clients carry neither, so their entities are built without a restriction: an
 * operator writing a client into `oauth.clients` never expressed one, and reading a
 * restriction into that silence would take `refresh_token` away from clients that work
 * today.
 *
 * @internal
 */
final class ClientRepository implements ClientRepositoryInterface
{
    private const string CLIENT_CREDENTIALS = 'client_credentials';

    /**
     * @param array<string, array{name: string, redirect_uris: list<string>}> $clients
     */
    public function __construct(
        private readonly array $clients,
        private readonly ClientMetadataResolverInterface $clientMetadataResolver,
        private readonly DynamicClientStoreInterface $dynamicClientStore,
    ) {
    }

    public function getClientEntity(string $clientIdentifier): ?ClientEntityInterface
    {
        // Pre-registered public clients are authoritative and resolve first, so
        // they work even when DCR and CIMD are disabled.
        $client = $this->clients[$clientIdentifier] ?? null;
        if ($client !== null) {
            return new ClientEntity($clientIdentifier, $client['name'], $client['redirect_uris'], preRegistered: true);
        }

        // A URL-form client_id is a CIMD client; anything else may be a
        // dynamically registered client.
        if ($this->looksLikeUrl($clientIdentifier)) {
            $metadata = $this->clientMetadataResolver->resolve($clientIdentifier);
            if ($metadata === null) {
                return null;
            }

            return new ClientEntity($metadata->clientId, $metadata->name, $metadata->redirectUris);
        }

        $dynamic = $this->dynamicClientStore->find($clientIdentifier);
        if ($dynamic === null) {
            return null;
        }

        // The registered grants and scopes travel with the entity: league asks it whether
        // it supports the grant in play, so the metadata RFC 7591 had us record is finally
        // answered from rather than merely echoed back.
        return new ClientEntity(
            $dynamic->identifier,
            $dynamic->name,
            $dynamic->redirectUris,
            $dynamic->confidential,
            grantTypes: $dynamic->grantTypes,
            scopes: $dynamic->scopes,
            serviceUserId: $dynamic->serviceUserId,
        );
    }

    /**
     * league checks the grant against the entity via ClientEntity::supportsGrantType(),
     * which is consulted for every grant rather than only the ones that reach here. This
     * method answers the narrower question of whether the credentials are good, and reads
     * `$grantType` only to keep client_credentials and a bound client's secret to each other.
     */
    public function validateClient(string $clientIdentifier, ?string $clientSecret, ?string $grantType): bool
    {
        // client_credentials authenticates nobody but the client, so only a confidential
        // client bound to a service user may use it: the token needs that user as subject.
        // Pre-registered, CIMD and dynamically registered clients are refused here, not
        // only by ClientEntity::supportsGrantType().
        if ($grantType === self::CLIENT_CREDENTIALS) {
            return $this->validateServiceUserClient($clientIdentifier, $clientSecret);
        }

        // Pre-registered clients are public: PKCE on the auth-code flow, no secret.
        if (isset($this->clients[$clientIdentifier])) {
            return true;
        }

        // CIMD clients are public: PKCE, no secret.
        if ($this->looksLikeUrl($clientIdentifier)) {
            return $this->clientMetadataResolver->resolve($clientIdentifier) !== null;
        }

        $dynamic = $this->dynamicClientStore->find($clientIdentifier);
        if ($dynamic === null) {
            return false;
        }

        // A bound client's secret opens client_credentials and nothing else.
        if ($dynamic->serviceUserId !== null) {
            return false;
        }

        // Public dynamic clients authenticate via PKCE and carry no secret.
        if (!$dynamic->confidential) {
            return true;
        }

        return $this->secretMatches($dynamic->secretHash, $clientSecret);
    }

    private function validateServiceUserClient(string $clientIdentifier, ?string $clientSecret): bool
    {
        if (isset($this->clients[$clientIdentifier]) || $this->looksLikeUrl($clientIdentifier)) {
            return false;
        }

        $client = $this->dynamicClientStore->find($clientIdentifier);
        if ($client === null || $client->serviceUserId === null || !$client->confidential) {
            return false;
        }

        return $this->secretMatches($client->secretHash, $clientSecret);
    }

    /**
     * Constant-time comparison against the stored hash. A client without a stored hash
     * never matches, so a record without a shared secret fails closed.
     */
    private function secretMatches(?string $secretHash, ?string $clientSecret): bool
    {
        return $secretHash !== null
            && $clientSecret !== null
            && $clientSecret !== ''
            && hash_equals($secretHash, hash('sha256', $clientSecret));
    }

    private function looksLikeUrl(string $clientIdentifier): bool
    {
        return str_starts_with($clientIdentifier, 'https://') || str_starts_with($clientIdentifier, 'http://');
    }
}
