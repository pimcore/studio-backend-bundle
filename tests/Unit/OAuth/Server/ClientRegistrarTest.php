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
use Pimcore\Bundle\StudioBackendBundle\OAuth\Dto\DynamicClient;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Exception\ClientRegistrationException;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Registry\ConfigProtectedResourceRegistry;
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

            public function findByMetadataHash(string $metadataHash): ?DynamicClient
            {
                foreach ($this->saved as $client) {
                    if ($client->metadataHash === $metadataHash) {
                        return $client;
                    }
                }

                return null;
            }
        };

        $this->registrar = $this->createRegistrar('mcp:read', 'mcp:write');
    }

    /**
     * A registrar whose scope catalogue is the real registry, derived from a single
     * protected resource declaring exactly the given scopes.
     */
    private function createRegistrar(string ...$scopes): ClientRegistrar
    {
        return new ClientRegistrar(
            $this->store,
            new ScopeRegistry(
                new ConfigProtectedResourceRegistry([
                    ['uri' => 'https://example.com/pimcore-mcp', 'scopes_supported' => $scopes],
                ]),
            ),
        );
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

    /**
     * An open registration endpoint is the abuse surface: without this, a client that
     * re-registers on every start, or a caller hammering the endpoint, grows the table
     * without bound. Matching on the client-chosen metadata makes the repeat a no-op.
     */
    public function testRepeatRegistrationReturnsTheSameClientAndStoresNoSecondRow(): void
    {
        $metadata = [
            'client_name' => 'Repeat',
            'redirect_uris' => ['https://app.example/cb'],
            'token_endpoint_auth_method' => 'none',
        ];

        $first = $this->registrar->register($metadata);
        $second = $this->registrar->register($metadata);

        $this->assertSame($first->identifier, $second->identifier);
        $this->assertCount(1, $this->store->saved);
        // Still a valid RFC 7591 registration response.
        $this->assertSame($first->redirectUris, $second->redirectUris);
        $this->assertSame('none', $second->tokenEndpointAuthMethod);
        $this->assertNull($second->secret);
    }

    /**
     * RFC 7591 gives the order of redirect_uris no meaning, so the same client listing
     * them differently is still the same client.
     */
    public function testRedirectUriOrderDoesNotCreateASecondClient(): void
    {
        $first = $this->registrar->register([
            'client_name' => 'Ordered',
            'redirect_uris' => ['https://app.example/a', 'https://app.example/b'],
            'token_endpoint_auth_method' => 'none',
        ]);
        $second = $this->registrar->register([
            'client_name' => 'Ordered',
            'redirect_uris' => ['https://app.example/b', 'https://app.example/a'],
            'token_endpoint_auth_method' => 'none',
        ]);

        $this->assertSame($first->identifier, $second->identifier);
        $this->assertCount(1, $this->store->saved);
    }

    public function testDifferingMetadataCreatesANewClient(): void
    {
        $first = $this->registrar->register([
            'client_name' => 'One',
            'redirect_uris' => ['https://app.example/cb'],
            'token_endpoint_auth_method' => 'none',
        ]);
        $differentName = $this->registrar->register([
            'client_name' => 'Two',
            'redirect_uris' => ['https://app.example/cb'],
            'token_endpoint_auth_method' => 'none',
        ]);
        $differentUri = $this->registrar->register([
            'client_name' => 'One',
            'redirect_uris' => ['https://other.example/cb'],
            'token_endpoint_auth_method' => 'none',
        ]);

        $this->assertNotSame($first->identifier, $differentName->identifier);
        $this->assertNotSame($first->identifier, $differentUri->identifier);
        $this->assertCount(3, $this->store->saved);
    }

    /**
     * Confidential clients are deliberately never deduplicated. The secret is returned
     * once and kept only as a hash, so a repeat call can neither return the original
     * (it is unrecoverable) nor omit it (RFC 7591 requires client_secret for a
     * confidential client) nor reissue it (that would silently invalidate the secret an
     * already-deployed instance is using). A fresh record is the honest answer.
     */
    public function testConfidentialRegistrationIsNeverDeduplicated(): void
    {
        $metadata = [
            'client_name' => 'Confidential',
            'redirect_uris' => ['https://app.example/cb'],
            'token_endpoint_auth_method' => 'client_secret_basic',
        ];

        $first = $this->registrar->register($metadata);
        $second = $this->registrar->register($metadata);

        $this->assertNotSame($first->identifier, $second->identifier);
        $this->assertCount(2, $this->store->saved);
        // Each gets its own usable secret.
        $this->assertNotNull($first->secret);
        $this->assertNotNull($second->secret);
        $this->assertNotSame($first->secret, $second->secret);
    }

    /**
     * A confidential record must carry no digest at all, so it can never be returned by
     * the dedupe lookup even if a public client happened to hash to the same value.
     */
    public function testConfidentialClientStoresNoMetadataHash(): void
    {
        $result = $this->registrar->register([
            'redirect_uris' => ['https://app.example/cb'],
        ]);

        $stored = $this->store->find($result->identifier);
        $this->assertNotNull($stored);
        $this->assertTrue($stored->confidential);
        $this->assertNull($stored->metadataHash);
    }

    public function testPublicClientStoresAMetadataHash(): void
    {
        $result = $this->registrar->register([
            'redirect_uris' => ['https://app.example/cb'],
            'token_endpoint_auth_method' => 'none',
        ]);

        $stored = $this->store->find($result->identifier);
        $this->assertNotNull($stored);
        $this->assertNotNull($stored->metadataHash);
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
