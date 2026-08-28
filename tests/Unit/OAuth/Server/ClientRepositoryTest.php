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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\OAuth\Server;

use Codeception\Test\Unit;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Contract\ClientMetadataResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Dto\ClientMetadata;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Dto\DynamicClient;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Server\Entity\ClientEntity;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Server\Repository\ClientRepository;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Server\Repository\DynamicClientStoreInterface;
use function hash;

final class ClientRepositoryTest extends Unit
{
    /**
     * @param array<string, ClientMetadata> $resolvable
     * @param array<string, DynamicClient>  $dynamicClients
     */
    private function repository(array $resolvable = [], array $dynamicClients = []): ClientRepository
    {
        return new ClientRepository(
            $this->resolver($resolvable),
            $this->store($dynamicClients),
        );
    }

    /**
     * @param array<string, ClientMetadata> $resolvable
     */
    private function resolver(array $resolvable): ClientMetadataResolverInterface
    {
        return new class($resolvable) implements ClientMetadataResolverInterface {
            /**
             * @param array<string, ClientMetadata> $resolvable
             */
            public function __construct(private array $resolvable)
            {
            }

            public function resolve(string $clientId): ?ClientMetadata
            {
                return $this->resolvable[$clientId] ?? null;
            }
        };
    }

    /**
     * @param array<string, DynamicClient> $clients
     */
    private function store(array $clients): DynamicClientStoreInterface
    {
        return new class($clients) implements DynamicClientStoreInterface {
            /**
             * @param array<string, DynamicClient> $clients
             */
            public function __construct(private array $clients)
            {
            }

            public function save(DynamicClient $client): void
            {
                $this->clients[$client->identifier] = $client;
            }

            public function find(string $identifier): ?DynamicClient
            {
                return $this->clients[$identifier] ?? null;
            }
        };
    }

    public function testUnknownClientIsNull(): void
    {
        $this->assertNull($this->repository()->getClientEntity('nope'));
    }

    public function testUnknownClientDoesNotValidate(): void
    {
        $this->assertFalse($this->repository()->validateClient('nope', 'x', 'authorization_code'));
    }

    public function testResolvesCimdClientFromUrl(): void
    {
        $url = 'https://app.example/client.json';
        $repo = $this->repository([
            $url => new ClientMetadata($url, 'CIMD App', ['https://app.example/cb']),
        ]);

        $client = $repo->getClientEntity($url);
        $this->assertInstanceOf(ClientEntity::class, $client);
        $this->assertSame($url, $client->getIdentifier());
        // CIMD clients are public.
        $this->assertFalse($client->isConfidential());
        $this->assertTrue($repo->validateClient($url, null, 'authorization_code'));
    }

    public function testUnresolvableCimdUrlIsRejected(): void
    {
        $repo = $this->repository();
        $this->assertNull($repo->getClientEntity('https://unknown.example/client.json'));
        $this->assertFalse($repo->validateClient('https://unknown.example/client.json', null, 'authorization_code'));
    }

    public function testResolvesDynamicPublicClient(): void
    {
        $repo = $this->repository([], [
            'dcr_pub' => new DynamicClient(
                'dcr_pub',
                'Dyn Public',
                ['https://app/cb'],
                ['authorization_code'],
                ['mcp:read'],
                false,
                null,
            ),
        ]);

        $client = $repo->getClientEntity('dcr_pub');
        $this->assertInstanceOf(ClientEntity::class, $client);
        $this->assertSame('dcr_pub', $client->getIdentifier());
        $this->assertFalse($client->isConfidential());
        $this->assertTrue($repo->validateClient('dcr_pub', null, 'authorization_code'));
    }

    public function testDynamicConfidentialClientValidatesSecret(): void
    {
        $repo = $this->repository([], [
            'dcr_conf' => new DynamicClient(
                'dcr_conf',
                'Dyn Conf',
                ['https://app/cb'],
                ['authorization_code'],
                ['mcp:read'],
                true,
                hash('sha256', 'sekret'),
            ),
        ]);

        $this->assertTrue($repo->validateClient('dcr_conf', 'sekret', 'authorization_code'));
        $this->assertFalse($repo->validateClient('dcr_conf', 'wrong', 'authorization_code'));
        $this->assertFalse($repo->validateClient('dcr_conf', null, 'authorization_code'));
    }
}
