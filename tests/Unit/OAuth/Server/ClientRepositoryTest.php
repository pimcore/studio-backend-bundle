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
use Pimcore\Bundle\StudioBackendBundle\OAuth\Server\Entity\ClientEntity;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Server\Repository\ClientRepository;

final class ClientRepositoryTest extends Unit
{
    /**
     * @param array<string, ClientMetadata> $resolvable
     */
    private function repository(array $resolvable = []): ClientRepository
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
}
