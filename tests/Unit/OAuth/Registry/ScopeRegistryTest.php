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

    public function testCatalogueComesFromRuntimeRegisteredResources(): void
    {
        $resources = new ConfigProtectedResourceRegistry();
        $registry = new ScopeRegistry($resources);

        $resources->register(new ProtectedResource('https://example.com/my-bundle', ['mybundle:read'], []));

        $this->assertSame(['mybundle:read'], $registry->all());
    }

    public function testCatalogueIsTheUnionOfConfiguredAndRuntimeResources(): void
    {
        $resources = new ConfigProtectedResourceRegistry([
            ['uri' => 'https://example.com/pimcore-mcp', 'scopes_supported' => ['mcp:read']],
        ]);
        $registry = new ScopeRegistry($resources);

        $resources->register(new ProtectedResource('https://example.com/my-bundle', ['mybundle:read'], []));

        $this->assertSame(['mcp:read', 'mybundle:read'], $registry->all());
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
     * The catalogue must not be memoised. Resources are registered per request by a
     * `kernel.request` subscriber, so freezing on first use would make the answer
     * depend on whether anything happened to ask before registration ran, which is an
     * ordering bug that only shows up under some request paths.
     */
    public function testResourceRegisteredAfterAFirstLookupIsReflected(): void
    {
        $resources = new ConfigProtectedResourceRegistry();
        $registry = new ScopeRegistry($resources);

        // Prime both entry points before anything is registered.
        $this->assertSame([], $registry->all());
        $this->assertFalse($registry->has('late:read'));

        $resources->register(new ProtectedResource('https://example.com/late', ['late:read'], []));

        $this->assertSame(['late:read'], $registry->all());
        $this->assertTrue($registry->has('late:read'));
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
     * Re-registering a URI replaces the resource, so a scope that only the replaced
     * definition carried must disappear from the catalogue with it.
     */
    public function testReplacingAResourceReplacesItsScopes(): void
    {
        $resources = new ConfigProtectedResourceRegistry([
            ['uri' => 'https://example.com/a', 'scopes_supported' => ['old:read']],
        ]);
        $registry = new ScopeRegistry($resources);
        $this->assertSame(['old:read'], $registry->all());

        $resources->register(new ProtectedResource('https://example.com/a', ['new:read'], []));

        $this->assertSame(['new:read'], $registry->all());
    }

    /**
     * @param list<array{uri: string, scopes_supported?: list<string>}> $resources
     */
    private function scopeRegistry(array $resources): ScopeRegistry
    {
        return new ScopeRegistry(new ConfigProtectedResourceRegistry($resources));
    }
}
