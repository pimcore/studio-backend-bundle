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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\OAuth\Registry;

use Codeception\Test\Unit;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Contract\ProtectedResourceProviderInterface;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Dto\ProtectedResource;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Registry\ConfigProtectedResourceRegistry;

final class ConfigProtectedResourceRegistryTest extends Unit
{
    /**
     * A provider yielding exactly the given resources, counting how often it is asked so
     * the laziness and memoisation properties can be asserted.
     *
     * @param list<ProtectedResource> $resources
     */
    private function provider(array $resources, ?int &$calls = null): ProtectedResourceProviderInterface
    {
        return new class($resources, $calls) implements ProtectedResourceProviderInterface {
            /**
             * @param list<ProtectedResource> $resources
             */
            public function __construct(
                private readonly array $resources,
                private ?int &$calls,
            ) {
                $this->calls = 0;
            }

            public function resources(): iterable
            {
                ++$this->calls;

                yield from $this->resources;
            }
        };
    }

    public function testSeedsFromConfigAndCanonicalisesLookups(): void
    {
        $registry = new ConfigProtectedResourceRegistry([
            [
                'uri' => 'https://Example.com/pimcore-mcp/',
                'scopes_supported' => ['mcp:read'],
                'authorization_servers' => ['https://example.com/pimcore-oauth'],
            ],
        ]);

        // Lookup with a differently-cased, trailing-slash variant must hit.
        $this->assertTrue($registry->has('https://example.com/pimcore-mcp'));
        $resource = $registry->get('HTTPS://EXAMPLE.COM/pimcore-mcp');
        $this->assertInstanceOf(ProtectedResource::class, $resource);
        $this->assertSame('https://example.com/pimcore-mcp', $resource->canonicalUri);
        $this->assertCount(1, $registry->all());
    }

    public function testUnknownResourceReturnsNull(): void
    {
        $registry = new ConfigProtectedResourceRegistry();

        $this->assertFalse($registry->has('https://example.com/nope'));
        $this->assertNull($registry->get('https://example.com/nope'));
        $this->assertNull($registry->metadataFor('https://example.com/nope'));
        $this->assertSame([], $registry->all());
    }

    public function testProvidersContributeResources(): void
    {
        $registry = new ConfigProtectedResourceRegistry([], [
            $this->provider([
                new ProtectedResource('https://example.com/a', ['mcp:read'], []),
                new ProtectedResource('https://example.com/b', ['mcp:read'], []),
            ]),
        ]);

        $this->assertCount(2, $registry->all());
        $this->assertNotNull($registry->metadataFor('https://example.com/b'));
    }

    public function testCatalogueIsTheUnionOfConfigAndProviders(): void
    {
        $registry = new ConfigProtectedResourceRegistry(
            [['uri' => 'https://example.com/configured', 'scopes_supported' => ['cfg:read']]],
            [$this->provider([new ProtectedResource('https://example.com/provided', ['prov:read'], [])])],
        );

        $this->assertCount(2, $registry->all());
        $this->assertTrue($registry->has('https://example.com/configured'));
        $this->assertTrue($registry->has('https://example.com/provided'));
    }

    /**
     * An operator declaring a URI a bundle also provides has made a deliberate choice,
     * usually to narrow its scopes. Configuration is applied after providers so it wins.
     */
    public function testConfigWinsOverAProviderForTheSameUri(): void
    {
        $registry = new ConfigProtectedResourceRegistry(
            [['uri' => 'https://example.com/shared', 'scopes_supported' => ['narrow:read']]],
            [$this->provider([new ProtectedResource('https://example.com/shared', ['wide:read', 'wide:write'], [])])],
        );

        $resource = $registry->get('https://example.com/shared');
        $this->assertNotNull($resource);
        $this->assertSame(['narrow:read'], $resource->scopesSupported);
        $this->assertCount(1, $registry->all());
    }

    /**
     * That precedence has to survive spelling: an operator writing a trailing slash or a
     * differently-cased host is naming the same resource, not adding a second one.
     */
    public function testConfigPrecedenceIsMatchedCanonically(): void
    {
        $registry = new ConfigProtectedResourceRegistry(
            [['uri' => 'https://EXAMPLE.com:443/shared/', 'scopes_supported' => ['narrow:read']]],
            [$this->provider([new ProtectedResource('https://example.com/shared', ['wide:read'], [])])],
        );

        $this->assertCount(1, $registry->all());
        $resource = $registry->get('https://example.com/shared');
        $this->assertNotNull($resource);
        $this->assertSame(['narrow:read'], $resource->scopesSupported);
        $this->assertSame('https://example.com/shared', $resource->canonicalUri);
    }

    /**
     * Nothing is asked of a provider until the registry is actually read. The tagged
     * iterator is lazy, so a request that never touches OAuth does not even instantiate
     * one; this pins the half of that property which lives in this class.
     */
    public function testProvidersAreNotConsultedUntilTheRegistryIsRead(): void
    {
        $calls = null;
        $provider = $this->provider([new ProtectedResource('https://example.com/a', [], [])], $calls);

        new ConfigProtectedResourceRegistry([], [$provider]);

        $this->assertSame(0, $calls, 'Constructing the registry must not consult providers.');
    }

    /**
     * Resolved once per instance. Safe now that nothing can add a resource after
     * construction - which is exactly what removing register() from the interface
     * guarantees.
     */
    public function testProvidersAreConsultedOnlyOnce(): void
    {
        $calls = null;
        $registry = new ConfigProtectedResourceRegistry(
            [],
            [$this->provider([new ProtectedResource('https://example.com/a', [], [])], $calls)],
        );

        $registry->all();
        $registry->has('https://example.com/a');
        $registry->get('https://example.com/a');
        $registry->metadataFor('https://example.com/a');

        $this->assertSame(1, $calls);
    }

    /**
     * The registry is a shared service with no reset hook, so a worker runtime keeps one
     * instance across requests. It must therefore answer the same thing every time: the
     * accumulation that a per-request `register()` produced is what this design removes.
     */
    public function testRepeatedReadsAreStable(): void
    {
        $registry = new ConfigProtectedResourceRegistry(
            [['uri' => 'https://example.com/configured']],
            [$this->provider([new ProtectedResource('https://example.com/provided', [], [])])],
        );

        $first = $registry->all();

        for ($i = 0; $i < 3; ++$i) {
            $this->assertEquals($first, $registry->all());
            $this->assertCount(2, $registry->all());
        }
    }

    public function testMetadataForReturnsRfc9728Document(): void
    {
        $registry = new ConfigProtectedResourceRegistry([
            [
                'uri' => 'https://example.com/pimcore-mcp',
                'scopes_supported' => ['mcp:read'],
                'authorization_servers' => ['https://example.com/pimcore-oauth'],
            ],
        ]);

        $metadata = $registry->metadataFor('https://example.com/pimcore-mcp');
        $this->assertNotNull($metadata);
        $this->assertSame(
            [
                'resource' => 'https://example.com/pimcore-mcp',
                'authorization_servers' => ['https://example.com/pimcore-oauth'],
                'scopes_supported' => ['mcp:read'],
                'bearer_methods_supported' => ['header'],
            ],
            $metadata->toArray()
        );
    }

    /**
     * A provider supplying a non-canonical URI has it stored canonically, so the RFC 9728
     * document echoes the canonical `resource` whatever the provider spelled. Providers
     * are told they need not canonicalise; this is what makes that true.
     */
    public function testProvidedResourcesAreStoredCanonically(): void
    {
        $registry = new ConfigProtectedResourceRegistry([], [
            $this->provider([new ProtectedResource('https://EXAMPLE.com:443/pimcore-mcp/', ['mcp:read'], [])]),
        ]);

        $resource = $registry->get('https://example.com/pimcore-mcp');

        $this->assertNotNull($resource);
        $this->assertSame('https://example.com/pimcore-mcp', $resource->canonicalUri);
        $this->assertSame(
            'https://example.com/pimcore-mcp',
            $registry->metadataFor('https://example.com/pimcore-mcp')?->toArray()['resource'] ?? null
        );
    }
}
