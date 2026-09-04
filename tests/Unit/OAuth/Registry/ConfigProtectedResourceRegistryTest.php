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

final class ConfigProtectedResourceRegistryTest extends Unit
{
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

    public function testRuntimeRegistrationSupportsMultipleResources(): void
    {
        $registry = new ConfigProtectedResourceRegistry();
        $registry->register(new ProtectedResource('https://example.com/a', ['mcp:read'], []));
        $registry->register(new ProtectedResource('https://example.com/b', ['mcp:read'], []));

        $this->assertCount(2, $registry->all());
        $this->assertNotNull($registry->metadataFor('https://example.com/b'));
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
     * A resource registered at runtime with a non-canonical URI is stored canonically,
     * so the RFC 9728 document echoes the canonical `resource` whatever the caller
     * spelled. Callers are told they need not canonicalise; this is what makes that true.
     */
    public function testRuntimeRegistrationCanonicalisesTheStoredResource(): void
    {
        $registry = new ConfigProtectedResourceRegistry();
        $registry->register(new ProtectedResource('https://EXAMPLE.com:443/pimcore-mcp/', ['mcp:read'], []));

        $resource = $registry->get('https://example.com/pimcore-mcp');

        $this->assertNotNull($resource);
        $this->assertSame('https://example.com/pimcore-mcp', $resource->canonicalUri);
        $this->assertSame(
            'https://example.com/pimcore-mcp',
            $registry->metadataFor('https://example.com/pimcore-mcp')?->toArray()['resource'] ?? null
        );
    }
}
