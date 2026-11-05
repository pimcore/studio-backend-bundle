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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Support\Fixture;

use Pimcore\Bundle\StudioBackendBundle\OAuth\Dto\DynamicClient;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Server\Repository\DynamicClientStoreInterface;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Server\Repository\ServiceUserClientStoreInterface;

/**
 * One client table seen through both stores, as in the database: the client repository
 * reads what the service-user client store wrote, so a rotated secret is visible at once.
 *
 * @internal
 */
final class InMemoryOAuthClientStore implements DynamicClientStoreInterface, ServiceUserClientStoreInterface
{
    /**
     * @var array<string, DynamicClient>
     */
    private array $clients = [];

    public function save(DynamicClient $client): void
    {
        $this->clients[$client->identifier] = $client;
    }

    public function find(string $identifier): ?DynamicClient
    {
        return $this->clients[$identifier] ?? null;
    }

    public function findByMetadataHash(string $metadataHash): ?DynamicClient
    {
        foreach ($this->clients as $client) {
            if (!$client->confidential && $client->metadataHash === $metadataHash) {
                return $client;
            }
        }

        return null;
    }

    public function findByServiceUserId(int $serviceUserId): ?DynamicClient
    {
        foreach ($this->clients as $client) {
            if ($client->serviceUserId === $serviceUserId) {
                return $client;
            }
        }

        return null;
    }

    public function replaceSecretHash(string $identifier, string $secretHash): void
    {
        $client = $this->clients[$identifier] ?? null;
        if ($client === null) {
            return;
        }

        $this->clients[$identifier] = new DynamicClient(
            $client->identifier,
            $client->name,
            $client->redirectUris,
            $client->grantTypes,
            $client->scopes,
            $client->confidential,
            $secretHash,
            $client->metadataHash,
            $client->createdAt,
            $client->serviceUserId,
        );
    }
}
