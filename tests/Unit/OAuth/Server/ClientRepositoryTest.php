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
            [
                'studio-mcp' => [
                    'name' => 'Studio MCP',
                    'redirect_uris' => ['https://localhost/callback'],
                    'confidential' => true,
                    'secret' => 'top-secret',
                    'service_user' => 21,
                ],
                'public-client' => [
                    'name' => 'Public Client',
                    'redirect_uris' => ['https://localhost/cb'],
                    'confidential' => false,
                ],
            ],
            $this->resolver($resolvable),
            $this->store($dynamicClients),
        );
    }

    /**
     * @param array<string, ClientMetadata> $resolvable
     */
    private function resolver(array $resolvable): ClientMetadataResolverInterface
    {
        return new class ($resolvable) implements ClientMetadataResolverInterface {
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
        return new class ($clients) implements DynamicClientStoreInterface {
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

    public function testResolvesClientEntityWithServiceUser(): void
    {
        $client = $this->repository()->getClientEntity('studio-mcp');
        $this->assertInstanceOf(ClientEntity::class, $client);
        $this->assertSame('studio-mcp', $client->getIdentifier());
        $this->assertTrue($client->isConfidential());
        $this->assertSame(21, $client->getServiceUserId());
    }

    public function testUnknownClientIsNull(): void
    {
        $this->assertNull($this->repository()->getClientEntity('nope'));
    }

    public function testConfidentialClientRequiresMatchingSecret(): void
    {
        $repo = $this->repository();
        $this->assertTrue($repo->validateClient('studio-mcp', 'top-secret', 'client_credentials'));
        $this->assertFalse($repo->validateClient('studio-mcp', 'wrong', 'client_credentials'));
        $this->assertFalse($repo->validateClient('studio-mcp', null, 'client_credentials'));
    }

    public function testPublicClientNeedsNoSecret(): void
    {
        $this->assertTrue($this->repository()->validateClient('public-client', null, 'authorization_code'));
    }

    public function testPublicClientCannotUseClientCredentials(): void
    {
        // A public client has no secret and must not obtain a service-account
        // token via client_credentials with only its client id.
        $this->assertFalse($this->repository()->validateClient('public-client', null, 'client_credentials'));
    }

    public function testUnknownClientDoesNotValidate(): void
    {
        $this->assertFalse($this->repository()->validateClient('nope', 'x', 'client_credentials'));
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
        // CIMD clients are public and carry no service user.
        $this->assertFalse($client->isConfidential());
        $this->assertNull($client->getServiceUserId());
        $this->assertTrue($repo->validateClient($url, null, 'authorization_code'));
    }

    public function testUnresolvableCimdUrlIsRejected(): void
    {
        $repo = $this->repository();
        $this->assertNull($repo->getClientEntity('https://unknown.example/client.json'));
        $this->assertFalse($repo->validateClient('https://unknown.example/client.json', null, 'authorization_code'));
    }

    public function testCimdClientCannotUseClientCredentials(): void
    {
        $url = 'https://app.example/client.json';
        $repo = $this->repository([
            $url => new ClientMetadata($url, 'CIMD App', ['https://app.example/cb']),
        ]);

        $this->assertFalse($repo->validateClient($url, null, 'client_credentials'));
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
        // Dynamic clients never carry a service user.
        $this->assertNull($client->getServiceUserId());
        $this->assertTrue($repo->validateClient('dcr_pub', null, 'authorization_code'));
    }

    public function testDynamicClientCannotUseClientCredentials(): void
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

        // Even a confidential dynamic client (with a valid secret) has no service
        // user and must be rejected for client_credentials.
        $this->assertFalse($repo->validateClient('dcr_conf', 'sekret', 'client_credentials'));
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
