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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\OAuth\Resolver;

use Codeception\Test\Unit;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Dto\ProtectedResource;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Registry\ConfigProtectedResourceRegistry;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Resolver\RequestResourceResolver;
use Symfony\Component\HttpFoundation\Request;

/**
 * @internal
 */
final class RequestResourceResolverTest extends Unit
{
    private const string ISSUER = 'https://pimcore.example.com';

    private const string STUDIO_SERVER = self::ISSUER . '/pimcore-mcp/studio/product-read';

    private const string AGENT_GROUP = self::ISSUER . '/pimcore-mcp/agent/content';

    public function testResolvesTheStudioServerItsRequestAddresses(): void
    {
        $resource = $this->resolve('/pimcore-mcp/studio/product-read', self::STUDIO_SERVER);

        $this->assertNotNull($resource);
        $this->assertSame(self::STUDIO_SERVER, $resource->canonicalUri);
    }

    public function testResolvesAnEndpointOwnedByAnotherBundle(): void
    {
        // The regression this replaces: deriving the audience from a path this
        // bundle knows only worked for its own servers, so an endpoint registered
        // by another bundle could never be reached with an audience-bound token.
        $resource = $this->resolve('/pimcore-mcp/agent/content', self::STUDIO_SERVER, self::AGENT_GROUP);

        $this->assertNotNull($resource);
        $this->assertSame(self::AGENT_GROUP, $resource->canonicalUri);
    }

    public function testUnregisteredEndpointResolvesToNothing(): void
    {
        // No registration means no audience a token could carry for this endpoint.
        $this->assertNull($this->resolve('/pimcore-mcp/agent/content', self::STUDIO_SERVER));
    }

    public function testPrefixOnlyMatchesOnASegmentBoundary(): void
    {
        $this->assertNull($this->resolve('/pimcore-mcp/agent/contentx', self::AGENT_GROUP));
    }

    public function testMostSpecificRegistrationWins(): void
    {
        $resource = $this->resolve(
            '/pimcore-mcp/agent/content',
            self::ISSUER . '/pimcore-mcp',
            self::AGENT_GROUP,
        );

        $this->assertNotNull($resource);
        $this->assertSame(self::AGENT_GROUP, $resource->canonicalUri);
    }

    public function testIssuerIsPreferredOverTheRequestHost(): void
    {
        // Behind a proxy the request host is not the issuer; comparing the two
        // would refuse a token issued for the resource as registered.
        $resource = $this->resolve('/pimcore-mcp/studio/product-read', self::STUDIO_SERVER);

        $this->assertNotNull($resource);
        $this->assertSame(self::STUDIO_SERVER, $resource->canonicalUri);
    }

    public function testWithoutAnIssuerTheRequestHostIsUsed(): void
    {
        $registry = new ConfigProtectedResourceRegistry();
        $registry->register(new ProtectedResource('http://localhost/pimcore-mcp/agent/content', [], []));

        $resource = (new RequestResourceResolver($registry))->resolve(
            Request::create('http://localhost/pimcore-mcp/agent/content'),
        );

        $this->assertNotNull($resource);
        $this->assertSame('http://localhost/pimcore-mcp/agent/content', $resource->canonicalUri);
    }

    /**
     * A resource carrying a query cannot be what an endpoint is, and ranking on the whole
     * identifier would let a long query outrank the resource that actually matches.
     */
    public function testAQueryBearingResourceNeverShadowsTheRealOne(): void
    {
        $resource = $this->resolve(
            '/pimcore-mcp/studio/product-read',
            self::STUDIO_SERVER . '?tenant=a-very-long-tenant-identifier',
            self::STUDIO_SERVER,
        );

        $this->assertNotNull($resource);
        $this->assertSame(self::STUDIO_SERVER, $resource->canonicalUri);
    }

    public function testAQueryBearingResourceAloneResolvesToNothing(): void
    {
        $this->assertNull($this->resolve(
            '/pimcore-mcp/studio/product-read',
            self::STUDIO_SERVER . '?tenant=a',
        ));
    }

    private function resolve(string $path, string ...$registered): ?ProtectedResource
    {
        $registry = new ConfigProtectedResourceRegistry();
        foreach ($registered as $uri) {
            $registry->register(new ProtectedResource($uri, [], []));
        }

        // A request arriving on a host that is not the issuer, which is the shape
        // every proxied deployment has.
        return (new RequestResourceResolver($registry, self::ISSUER))
            ->resolve(Request::create('http://internal.local' . $path));
    }
}
