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
use function hash_equals;
use function is_string;
use function str_starts_with;

/**
 * Serves clients from two sources: the pre-registered first-party clients in
 * bundle configuration, and clients identified by a URL-form client_id resolved
 * on demand from a Client ID Metadata Document (CIMD). Config clients win.
 *
 * @internal
 */
final class ClientRepository implements ClientRepositoryInterface
{
    /**
     * @param array<string, array{
     *     name: string,
     *     redirect_uris?: list<string>,
     *     confidential?: bool,
     *     secret?: ?string,
     *     service_user?: ?int
     * }> $clients
     */
    public function __construct(
        private readonly array $clients,
        private readonly ClientMetadataResolverInterface $clientMetadataResolver,
    ) {
    }

    public function getClientEntity(string $clientIdentifier): ?ClientEntityInterface
    {
        $client = $this->clients[$clientIdentifier] ?? null;
        if ($client !== null) {
            return new ClientEntity(
                $clientIdentifier,
                $client['name'],
                $client['redirect_uris'] ?? [],
                $client['confidential'] ?? false,
                isset($client['service_user']) ? (int) $client['service_user'] : null,
            );
        }

        if (!$this->looksLikeUrl($clientIdentifier)) {
            return null;
        }

        $metadata = $this->clientMetadataResolver->resolve($clientIdentifier);
        if ($metadata === null) {
            return null;
        }

        // CIMD clients are public and never act as a service user.
        return new ClientEntity($metadata->clientId, $metadata->name, $metadata->redirectUris, false, null);
    }

    public function validateClient(string $clientIdentifier, ?string $clientSecret, ?string $grantType): bool
    {
        $client = $this->clients[$clientIdentifier] ?? null;
        if ($client !== null) {
            // Public clients authenticate via PKCE on the auth-code flow and carry no
            // secret. They MUST NOT be accepted for client_credentials, which requires
            // client authentication — otherwise a public client with a service_user
            // could obtain a service-account token with only its (public) client id.
            if (($client['confidential'] ?? false) === false) {
                return $grantType !== 'client_credentials';
            }

            $secret = $client['secret'] ?? null;

            return is_string($secret) && $secret !== '' && hash_equals($secret, (string) $clientSecret);
        }

        if (!$this->looksLikeUrl($clientIdentifier)) {
            return false;
        }

        // CIMD clients are public: PKCE, no secret, never client_credentials.
        if ($grantType === 'client_credentials') {
            return false;
        }

        return $this->clientMetadataResolver->resolve($clientIdentifier) !== null;
    }

    private function looksLikeUrl(string $clientIdentifier): bool
    {
        return str_starts_with($clientIdentifier, 'https://') || str_starts_with($clientIdentifier, 'http://');
    }
}
