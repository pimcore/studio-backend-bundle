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
 * @internal
 */
final class ClientRepository implements ClientRepositoryInterface
{
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
            return new ClientEntity($clientIdentifier, $client['name'], $client['redirect_uris']);
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

        return new ClientEntity($dynamic->identifier, $dynamic->name, $dynamic->redirectUris, $dynamic->confidential);
    }

    public function validateClient(string $clientIdentifier, ?string $clientSecret, ?string $grantType): bool
    {
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

        // Public dynamic clients authenticate via PKCE and carry no secret.
        if (!$dynamic->confidential) {
            return true;
        }

        return $dynamic->secretHash !== null
            && hash_equals($dynamic->secretHash, hash('sha256', (string) $clientSecret));
    }

    private function looksLikeUrl(string $clientIdentifier): bool
    {
        return str_starts_with($clientIdentifier, 'https://') || str_starts_with($clientIdentifier, 'http://');
    }
}
