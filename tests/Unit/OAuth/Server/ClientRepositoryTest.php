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
     * @param array<string, array{name: string, redirect_uris: list<string>}> $clients
     * @param array<string, ClientMetadata>                                    $resolvable
     * @param array<string, DynamicClient>                                     $dynamicClients
     */
    private function repository(array $clients = [], array $resolvable = [], array $dynamicClients = []): ClientRepository
    {
        return new ClientRepository(
            $clients,
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

            public function findByMetadataHash(string $metadataHash): ?DynamicClient
            {
                foreach ($this->clients as $client) {
                    if ($client->metadataHash === $metadataHash) {
                        return $client;
                    }
                }

                return null;
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

    public function testResolvesPreRegisteredPublicClient(): void
    {
        $repo = $this->repository([
            'studio-mcp' => ['name' => 'Studio MCP', 'redirect_uris' => ['http://localhost:6274/cb']],
        ]);

        $client = $repo->getClientEntity('studio-mcp');
        $this->assertInstanceOf(ClientEntity::class, $client);
        $this->assertSame('studio-mcp', $client->getIdentifier());
        $this->assertSame(['http://localhost:6274/cb'], $client->getRedirectUri());
        // Pre-registered clients are public: PKCE, no secret.
        $this->assertFalse($client->isConfidential());
        // ...and flagged as admin-declared, so the consent screen shows them verified.
        $this->assertTrue($client->isPreRegistered());
    }

    public function testPreRegisteredClientValidatesWithoutSecret(): void
    {
        $repo = $this->repository([
            'studio-mcp' => ['name' => 'Studio MCP', 'redirect_uris' => ['http://localhost:6274/cb']],
        ]);

        // Public client: valid with no secret, and a supplied secret is simply ignored.
        $this->assertTrue($repo->validateClient('studio-mcp', null, 'authorization_code'));
        $this->assertTrue($repo->validateClient('studio-mcp', 'anything', 'authorization_code'));
    }

    public function testPreRegisteredClientTakesPrecedenceOverDynamicStore(): void
    {
        $repo = $this->repository(
            ['shared-id' => ['name' => 'Pre-registered', 'redirect_uris' => ['https://app/pre']]],
            [],
            [
                'shared-id' => new DynamicClient(
                    'shared-id',
                    'Dynamic',
                    ['https://app/dyn'],
                    ['authorization_code'],
                    ['mcp:read'],
                    false,
                    null,
                ),
            ],
        );

        // The admin-declared config entry is authoritative and resolves first.
        $client = $repo->getClientEntity('shared-id');
        $this->assertInstanceOf(ClientEntity::class, $client);
        $this->assertSame(['https://app/pre'], $client->getRedirectUri());
    }

    public function testResolvesCimdClientFromUrl(): void
    {
        $url = 'https://app.example/client.json';
        $repo = $this->repository([], [
            $url => new ClientMetadata($url, 'CIMD App', ['https://app.example/cb']),
        ]);

        $client = $repo->getClientEntity($url);
        $this->assertInstanceOf(ClientEntity::class, $client);
        $this->assertSame($url, $client->getIdentifier());
        // CIMD clients are public.
        $this->assertFalse($client->isConfidential());
        // Self-registered (URL-identified) → not pre-registered → shown as unverified.
        $this->assertFalse($client->isPreRegistered());
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
        $repo = $this->repository([], [], [
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
        // Self-registered via DCR → not pre-registered → shown as unverified.
        $this->assertFalse($client->isPreRegistered());
        $this->assertTrue($repo->validateClient('dcr_pub', null, 'authorization_code'));
    }

    /**
     * The defect this closes: the registered grants were parsed, validated, persisted and
     * echoed back in the registration response, then honoured by nothing. league consults
     * the entity, so the entity has to carry them.
     */
    public function testDynamicClientSupportsOnlyItsRegisteredGrants(): void
    {
        $repo = $this->repository([], [], [
            'dcr_authcode_only' => $this->dynamicClient('dcr_authcode_only', ['authorization_code']),
        ]);

        $client = $repo->getClientEntity('dcr_authcode_only');
        $this->assertInstanceOf(ClientEntity::class, $client);
        $this->assertTrue($client->supportsGrantType('authorization_code'));
        $this->assertFalse($client->supportsGrantType('refresh_token'));
    }

    public function testDynamicClientRegisteredForRefreshSupportsIt(): void
    {
        $repo = $this->repository([], [], [
            'dcr_both' => $this->dynamicClient('dcr_both', ['authorization_code', 'refresh_token']),
        ]);

        $client = $repo->getClientEntity('dcr_both');
        $this->assertInstanceOf(ClientEntity::class, $client);
        $this->assertTrue($client->supportsGrantType('authorization_code'));
        $this->assertTrue($client->supportsGrantType('refresh_token'));
    }

    /**
     * The most likely way this change breaks something. A client an operator wrote into
     * `oauth.clients` has no `grant_types` key to read, so inferring a restriction from
     * that silence would take `refresh_token` away from clients that work today - the
     * demo's own `swagger-ui` and `my-dev-pimcore` entries among them.
     */
    public function testPreRegisteredClientIsUnrestricted(): void
    {
        $repo = $this->repository([
            'swagger-ui' => ['name' => 'Data Hub Swagger UI', 'redirect_uris' => ['https://app/cb']],
        ]);

        $client = $repo->getClientEntity('swagger-ui');
        $this->assertInstanceOf(ClientEntity::class, $client);
        $this->assertTrue($client->supportsGrantType('authorization_code'));
        $this->assertTrue($client->supportsGrantType('refresh_token'));
    }

    /**
     * Same reasoning for CIMD: a metadata document carries no grant_types either.
     */
    public function testCimdClientIsUnrestricted(): void
    {
        $repo = $this->repository([], [
            'https://app.example/client.json' => new ClientMetadata(
                'https://app.example/client.json',
                'Cimd App',
                ['https://app.example/cb'],
            ),
        ]);

        $client = $repo->getClientEntity('https://app.example/client.json');
        $this->assertInstanceOf(ClientEntity::class, $client);
        $this->assertTrue($client->supportsGrantType('authorization_code'));
        $this->assertTrue($client->supportsGrantType('refresh_token'));
    }

    /**
     * Plumbing only for now: the registered scopes reach the entity so the scope work can
     * intersect against them, and nothing consults them yet.
     */
    public function testRegisteredScopesAreCarriedOntoTheEntity(): void
    {
        $repo = $this->repository([], [], [
            'dcr_scoped' => $this->dynamicClient('dcr_scoped', ['authorization_code'], ['mcp:read']),
        ]);

        $client = $repo->getClientEntity('dcr_scoped');
        $this->assertInstanceOf(ClientEntity::class, $client);
        $this->assertSame(['mcp:read'], $client->getRegisteredScopes());
    }

    public function testUnrestrictedClientsReportNullRegisteredScopes(): void
    {
        $repo = $this->repository([
            'cfg' => ['name' => 'Configured', 'redirect_uris' => ['https://app/cb']],
        ]);

        $client = $repo->getClientEntity('cfg');
        $this->assertInstanceOf(ClientEntity::class, $client);
        $this->assertNull($client->getRegisteredScopes());
    }

    /**
     * @param list<string> $grantTypes
     * @param list<string> $scopes
     */
    private function dynamicClient(string $id, array $grantTypes, array $scopes = []): DynamicClient
    {
        return new DynamicClient($id, 'Dyn', ['https://app/cb'], $grantTypes, $scopes, false, null);
    }

    public function testDynamicConfidentialClientValidatesSecret(): void
    {
        $repo = $this->repository([], [], [
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
