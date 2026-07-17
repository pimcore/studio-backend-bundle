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
use Pimcore\Bundle\StudioBackendBundle\OAuth\Server\Entity\ClientEntity;
use function hash_equals;
use function is_string;

/**
 * Serves the pre-registered first-party clients from bundle configuration.
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
    ) {
    }

    public function getClientEntity(string $clientIdentifier): ?ClientEntityInterface
    {
        $client = $this->clients[$clientIdentifier] ?? null;
        if ($client === null) {
            return null;
        }

        return new ClientEntity(
            $clientIdentifier,
            $client['name'],
            $client['redirect_uris'] ?? [],
            $client['confidential'] ?? false,
            isset($client['service_user']) ? (int) $client['service_user'] : null,
        );
    }

    public function validateClient(string $clientIdentifier, ?string $clientSecret, ?string $grantType): bool
    {
        $client = $this->clients[$clientIdentifier] ?? null;
        if ($client === null) {
            return false;
        }

        // Public clients authenticate without a secret (PKCE covers them).
        if (($client['confidential'] ?? false) === false) {
            return true;
        }

        $secret = $client['secret'] ?? null;

        return is_string($secret) && $secret !== '' && hash_equals($secret, (string) $clientSecret);
    }
}
