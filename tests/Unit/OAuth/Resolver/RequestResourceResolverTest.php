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
use function array_map;
use function array_values;

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

    /**
     * No issuer, no resolution - never the request host instead. The validator compares
     * a token's `aud` against the URI it is handed, so an audience rebuilt from the
     * caller-supplied `Host` would be compared with itself and pass.
     */
    public function testWithoutAnIssuerNothingResolves(): void
    {
        $registry = new ConfigProtectedResourceRegistry([
            ['uri' => 'http://localhost/pimcore-mcp/agent/content'],
        ]);

        $resource = (new RequestResourceResolver($registry))->resolve(
            Request::create('http://localhost/pimcore-mcp/agent/content'),
        );

        $this->assertNull($resource);
    }

    /**
     * The router decodes the path once before matching, so a percent-encoded request
     * reaches the same server as the plain one. Resolving on the raw path would miss that
     * server's own resource and fall back to the broader base, accepting a token bound
     * to the base at an endpoint that has its own audience.
     */
    public function testAPercentEncodedPathResolvesToTheResourceTheRouterServes(): void
    {
        $resource = $this->resolve('/pimcore-mcp/studio/%70roduct-read', self::ISSUER . '/pimcore-mcp', self::STUDIO_SERVER);

        $this->assertNotNull($resource);
        $this->assertSame(self::STUDIO_SERVER, $resource->canonicalUri);
    }

    /**
     * Decoded exactly once, like the router, which routes "%2570roduct-read" as the
     * literal "%70roduct-read" and so never to the product-read server.
     */
    public function testADoublyEncodedPathIsNotDecodedTwice(): void
    {
        $resource = $this->resolve('/pimcore-mcp/studio/%2570roduct-read', self::ISSUER . '/pimcore-mcp', self::STUDIO_SERVER);

        $this->assertNotNull($resource);
        $this->assertSame(self::ISSUER . '/pimcore-mcp', $resource->canonicalUri);
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
        // Registered as configuration: the registry is read-only since #2039, and
        // providers and configured entries resolve to the same list.
        $registry = new ConfigProtectedResourceRegistry(
            array_map(static fn (string $uri): array => ['uri' => $uri], array_values($registered)),
        );

        // A request arriving on a host that is not the issuer, which is the shape
        // every proxied deployment has.
        return (new RequestResourceResolver($registry, self::ISSUER))
            ->resolve(Request::create('http://internal.local' . $path));
    }
}
