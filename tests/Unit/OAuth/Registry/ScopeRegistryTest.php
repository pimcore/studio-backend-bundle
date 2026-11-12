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
use Pimcore\Bundle\StudioBackendBundle\OAuth\Registry\ScopeRegistry;

/**
 * @internal
 */
final class ScopeRegistryTest extends Unit
{
    public function testCatalogueComesFromConfiguredResources(): void
    {
        $registry = $this->scopeRegistry([
            ['uri' => 'https://example.com/pimcore-mcp', 'scopes_supported' => ['mcp:read', 'mcp:write']],
        ]);

        $this->assertSame(['mcp:read', 'mcp:write'], $registry->all());
        $this->assertTrue($registry->has('mcp:read'));
        $this->assertFalse($registry->has('nope:read'));
    }

    public function testCatalogueComesFromProvidedResources(): void
    {
        $registry = new ScopeRegistry(new ConfigProtectedResourceRegistry([], [
            $this->provider(new ProtectedResource('https://example.com/my-bundle', ['mybundle:read'], [])),
        ]));

        $this->assertSame(['mybundle:read'], $registry->all());
    }

    public function testCatalogueIsTheUnionOfConfiguredAndProvidedResources(): void
    {
        $registry = new ScopeRegistry(new ConfigProtectedResourceRegistry(
            [['uri' => 'https://example.com/pimcore-mcp', 'scopes_supported' => ['mcp:read']]],
            [$this->provider(new ProtectedResource('https://example.com/my-bundle', ['mybundle:read'], []))],
        ));

        // Providers resolve first, configuration second.
        $this->assertSame(['mybundle:read', 'mcp:read'], $registry->all());
    }

    public function testScopeSharedByTwoResourcesAppearsOnce(): void
    {
        $registry = $this->scopeRegistry([
            ['uri' => 'https://example.com/a', 'scopes_supported' => ['shared:read', 'a:write']],
            ['uri' => 'https://example.com/b', 'scopes_supported' => ['shared:read', 'b:write']],
        ]);

        $this->assertSame(['shared:read', 'a:write', 'b:write'], $registry->all());
    }

    /**
     * Reading the catalogue repeatedly must not change it. Resources can no longer be
     * added after construction, so both entry points answer the same thing however often
     * and in whatever order they are called.
     */
    public function testRepeatedLookupsAreStable(): void
    {
        $registry = new ScopeRegistry(new ConfigProtectedResourceRegistry([], [
            $this->provider(new ProtectedResource('https://example.com/a', ['a:read'], [])),
        ]));

        $this->assertTrue($registry->has('a:read'));
        $this->assertSame(['a:read'], $registry->all());
        $this->assertTrue($registry->has('a:read'));
        $this->assertSame(['a:read'], $registry->all());
        $this->assertFalse($registry->has('nope:read'));
    }

    public function testResourceDeclaringNoScopesContributesNothing(): void
    {
        $registry = $this->scopeRegistry([
            ['uri' => 'https://example.com/a', 'scopes_supported' => []],
            ['uri' => 'https://example.com/b', 'scopes_supported' => ['b:read']],
        ]);

        $this->assertSame(['b:read'], $registry->all());
    }

    public function testEmptyStringsAreNotScopes(): void
    {
        $registry = $this->scopeRegistry([
            ['uri' => 'https://example.com/a', 'scopes_supported' => ['', 'a:read', '']],
        ]);

        $this->assertSame(['a:read'], $registry->all());
    }

    /**
     * Configuration overriding a provided resource replaces it wholesale, so a scope only
     * the provider declared disappears from the catalogue with it. That is what lets an
     * operator narrow what a bundle offers.
     */
    public function testConfigOverridingAProviderReplacesItsScopes(): void
    {
        $registry = new ScopeRegistry(new ConfigProtectedResourceRegistry(
            [['uri' => 'https://example.com/a', 'scopes_supported' => ['narrow:read']]],
            [$this->provider(new ProtectedResource('https://example.com/a', ['wide:read', 'wide:write'], []))],
        ));

        $this->assertSame(['narrow:read'], $registry->all());
    }

    private function provider(ProtectedResource ...$resources): ProtectedResourceProviderInterface
    {
        return new class($resources) implements ProtectedResourceProviderInterface {
            /**
             * @param list<ProtectedResource> $resources
             */
            public function __construct(private readonly array $resources)
            {
            }

            public function resources(): iterable
            {
                yield from $this->resources;
            }
        };
    }

    /**
     * @param list<array{uri: string, scopes_supported?: list<string>}> $resources
     */
    private function scopeRegistry(array $resources): ScopeRegistry
    {
        return new ScopeRegistry(new ConfigProtectedResourceRegistry($resources));
    }
}
