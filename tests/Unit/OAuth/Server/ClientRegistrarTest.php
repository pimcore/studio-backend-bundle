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
use Pimcore\Bundle\StudioBackendBundle\OAuth\Contract\ScopeProviderInterface;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Dto\DynamicClient;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Exception\ClientRegistrationException;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Registry\ScopeRegistry;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Server\ClientRegistrar;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Server\Repository\DynamicClientStoreInterface;
use function hash;
use function str_starts_with;

final class ClientRegistrarTest extends Unit
{
    private DynamicClientStoreInterface $store;

    private ClientRegistrar $registrar;

    protected function _before(): void
    {
        $this->store = new class implements DynamicClientStoreInterface {
            /** @var array<string, DynamicClient> */
            public array $saved = [];

            public function save(DynamicClient $client): void
            {
                $this->saved[$client->identifier] = $client;
            }

            public function find(string $identifier): ?DynamicClient
            {
                return $this->saved[$identifier] ?? null;
            }
        };

        $this->registrar = $this->createRegistrar('mcp:read', 'mcp:write');
    }

    /**
     * A registrar whose scope catalogue is the real registry, fed by a single
     * provider contributing exactly the given scopes.
     */
    private function createRegistrar(string ...$scopes): ClientRegistrar
    {
        $provider = new class($scopes) implements ScopeProviderInterface {
            /**
             * @param list<string> $scopes
             */
            public function __construct(private readonly array $scopes)
            {
            }

            public function scopes(): array
            {
                return $this->scopes;
            }
        };

        return new ClientRegistrar($this->store, new ScopeRegistry([$provider]));
    }

    public function testRegistersPublicClient(): void
    {
        $result = $this->registrar->register([
            'client_name' => 'Public',
            'redirect_uris' => ['https://app.example/cb'],
            'token_endpoint_auth_method' => 'none',
            'grant_types' => ['authorization_code', 'refresh_token'],
            'scope' => 'mcp:read mcp:write',
        ]);

        $this->assertTrue(str_starts_with($result->identifier, 'dcr_'));
        $this->assertNull($result->secret);
        $this->assertSame('none', $result->tokenEndpointAuthMethod);
        $this->assertSame(['authorization_code', 'refresh_token'], $result->grantTypes);
        $this->assertSame(['mcp:read', 'mcp:write'], $result->scopes);

        $stored = $this->store->find($result->identifier);
        $this->assertNotNull($stored);
        $this->assertFalse($stored->confidential);
        $this->assertNull($stored->secretHash);
    }

    public function testRegistersConfidentialClientWithHashedSecret(): void
    {
        $result = $this->registrar->register([
            'redirect_uris' => ['https://app.example/cb'],
        ]);

        // Default auth method is confidential; a secret is issued once.
        $this->assertSame('client_secret_basic', $result->tokenEndpointAuthMethod);
        $this->assertNotNull($result->secret);

        $stored = $this->store->find($result->identifier);
        $this->assertNotNull($stored);
        $this->assertTrue($stored->confidential);
        // Only the hash is persisted, never the plaintext.
        $this->assertSame(hash('sha256', (string) $result->secret), $stored->secretHash);
    }

    public function testDefaultsGrantAndScope(): void
    {
        $result = $this->registrar->register(['redirect_uris' => ['https://app.example/cb']]);
        $this->assertSame(['authorization_code'], $result->grantTypes);
        $this->assertSame([], $result->scopes);
    }

    public function testAllowsLoopbackHttpRedirect(): void
    {
        $result = $this->registrar->register([
            'redirect_uris' => ['http://localhost:6274/oauth/callback'],
            'token_endpoint_auth_method' => 'none',
        ]);
        $this->assertSame(['http://localhost:6274/oauth/callback'], $result->redirectUris);
    }

    public function testRejectsMissingRedirectUris(): void
    {
        $this->expectException(ClientRegistrationException::class);
        $this->registrar->register(['client_name' => 'x']);
    }

    public function testRejectsNonLoopbackHttpRedirect(): void
    {
        $this->expectException(ClientRegistrationException::class);
        $this->registrar->register(['redirect_uris' => ['http://evil.example/cb']]);
    }

    public function testRejectsRedirectWithFragment(): void
    {
        $this->expectException(ClientRegistrationException::class);
        $this->registrar->register(['redirect_uris' => ['https://app.example/cb#frag']]);
    }

    public function testRejectsUnsupportedGrant(): void
    {
        $this->expectException(ClientRegistrationException::class);
        $this->registrar->register([
            'redirect_uris' => ['https://app.example/cb'],
            'grant_types' => ['client_credentials'],
        ]);
    }

    public function testRejectsUnsupportedScope(): void
    {
        $this->expectException(ClientRegistrationException::class);
        $this->registrar->register([
            'redirect_uris' => ['https://app.example/cb'],
            'scope' => 'admin:all',
        ]);
    }

    public function testRegistersScopeContributedByAnotherBundle(): void
    {
        // The allowed scopes are whatever the registry holds, not a fixed list.
        $result = $this->createRegistrar('datahub:read')->register([
            'redirect_uris' => ['https://app.example/cb'],
            'scope' => 'datahub:read',
        ]);

        $this->assertSame(['datahub:read'], $result->scopes);
    }

    /**
     * Deliberately NOT "the first scope in the registry": that order follows bundle
     * registration, so the same registration would yield different scopes on different
     * installations. A client that asks for no scope gets none.
     */
    public function testOmittedScopeYieldsNoScopeRegardlessOfTheRegistry(): void
    {
        $result = $this->createRegistrar('datahub:read', 'datahub:write')->register([
            'redirect_uris' => ['https://app.example/cb'],
        ]);

        $this->assertSame([], $result->scopes);
    }

    public function testEmptyRegistryYieldsNoDefaultScope(): void
    {
        $result = $this->createRegistrar()->register(['redirect_uris' => ['https://app.example/cb']]);

        $this->assertSame([], $result->scopes);
    }

    public function testRejectsScopeMissingFromTheRegistry(): void
    {
        // `mcp:write` is only ever supported because a provider contributes it.
        $this->expectException(ClientRegistrationException::class);
        $this->createRegistrar('mcp:read')->register([
            'redirect_uris' => ['https://app.example/cb'],
            'scope' => 'mcp:write',
        ]);
    }
}
