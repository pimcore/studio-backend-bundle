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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\Mcp;

use Codeception\Test\Unit;
use Mcp\Schema\ToolAnnotations;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Mcp\Dto\McpServerDefinition;
use Pimcore\Bundle\StudioBackendBundle\Mcp\McpServerResourceProvider;
use Pimcore\Bundle\StudioBackendBundle\Mcp\ProtectedResourceProvider;
use Pimcore\Bundle\StudioBackendBundle\Mcp\Registry\McpToolReference;
use Pimcore\Bundle\StudioBackendBundle\Mcp\Registry\McpToolRegistryInterface;
use Pimcore\Bundle\StudioBackendBundle\Mcp\Repository\McpServerConfigRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Dto\ProtectedResource;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Registry\ConfigProtectedResourceRegistry;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Resolver\RequestResourceResolver;
use Symfony\Component\HttpFoundation\Request;
use function array_map;
use function iterator_to_array;

/**
 * @internal
 */
final class McpServerResourceProviderTest extends Unit
{
    private const string ISSUER = 'https://pimcore.example.com';

    private const string PRODUCT_READ = self::ISSUER . '/pimcore-mcp/studio/product-read';

    public function testContributesOneResourcePerEnabledServer(): void
    {
        $resources = $this->resources([
            $this->server('product-read', ['read-tool']),
            $this->server('content-edit', ['write-tool']),
        ]);

        $this->assertSame(
            [self::PRODUCT_READ, self::ISSUER . '/pimcore-mcp/studio/content-edit'],
            array_map(static fn (ProtectedResource $resource): string => $resource->canonicalUri, $resources),
        );
        $this->assertSame([self::ISSUER], $resources[0]->authorizationServers);
    }

    public function testContributesNothingWhenOAuthIsDisabled(): void
    {
        $this->assertSame([], $this->resources([$this->server('product-read', ['read-tool'])], enabled: false));
    }

    /**
     * Fails closed: without an issuer there is no trustworthy base for the URI, and an
     * unregistered server refuses OAuth.
     */
    public function testContributesNothingWithoutAnIssuer(): void
    {
        $this->assertSame([], $this->resources([$this->server('product-read', ['read-tool'])], issuer: null));
    }

    public function testSkipsDisabledServersAndSlugsTheRouteCannotServe(): void
    {
        $resources = $this->resources([
            $this->server('product-read', ['read-tool'], enabled: false),
            $this->server('Not/A?Slug', ['read-tool']),
        ]);

        $this->assertSame([], $resources);
    }

    /**
     * With no declared scopes the server supports the scopes its tools require: a
     * read-only tool needs mcp:read, anything else the fail-safe mcp:write. Tool ids the
     * registry does not know contribute nothing. (#2039 made an empty default mean "no
     * scopes", so a fixed read/write fallback would advertise scopes no tool needs.)
     */
    public function testDerivesScopesFromTheAssignedTools(): void
    {
        [$readOnly, $mixed] = $this->resources([
            $this->server('product-read', ['read-tool', 'unknown-tool']),
            $this->server('content-edit', ['read-tool', 'write-tool']),
        ]);

        $this->assertSame(['mcp:read'], $readOnly->scopesSupported);
        $this->assertSame(['mcp:read', 'mcp:write'], $mixed->scopesSupported);
    }

    public function testDeclaredScopesOverrideTheDerivedOnes(): void
    {
        [$resource] = $this->resources([$this->server('product-read', ['write-tool'], scopes: ['mcp:read'])]);

        $this->assertSame(['mcp:read'], $resource->scopesSupported);
    }

    /**
     * Read when the registry is first consulted, not at container build, so a server the
     * repository returns is covered whether it came from configuration or was created
     * through the Studio API afterwards - and nothing is read before that.
     */
    public function testReadsTheRepositoryLazily(): void
    {
        $calls = 0;
        $repository = $this->makeEmpty(McpServerConfigRepositoryInterface::class, [
            'list' => function () use (&$calls): array {
                ++$calls;

                return [$this->server('created-at-runtime', ['read-tool'])];
            },
        ]);
        $registry = new ConfigProtectedResourceRegistry(
            [],
            [new McpServerResourceProvider($repository, $this->toolRegistry(), true, self::ISSUER)],
        );

        $this->assertSame(0, $calls);
        $this->assertTrue($registry->has(self::ISSUER . '/pimcore-mcp/studio/created-at-runtime'));
        $this->assertSame(1, $calls);
    }

    public function testAnUnloadableServerListContributesNothing(): void
    {
        $repository = $this->makeEmpty(McpServerConfigRepositoryInterface::class, [
            'list' => static fn () => throw new NotFoundException('MCP server', 'gone'),
        ]);

        $provider = new McpServerResourceProvider($repository, $this->toolRegistry(), true, self::ISSUER);

        $this->assertSame([], iterator_to_array($provider->resources(), false));
    }

    /**
     * The point of a resource per server: next to the shared base, a request to a
     * managed server resolves to that server's own audience, while an endpoint outside
     * the managed servers still resolves to the base.
     */
    public function testAManagedServerIsItsOwnAudienceNextToTheBase(): void
    {
        $repository = $this->makeEmpty(McpServerConfigRepositoryInterface::class, [
            'list' => [$this->server('product-read', ['read-tool'])],
        ]);
        $registry = new ConfigProtectedResourceRegistry([], [
            new ProtectedResourceProvider(true, self::ISSUER),
            new McpServerResourceProvider($repository, $this->toolRegistry(), true, self::ISSUER),
        ]);
        $resolver = new RequestResourceResolver($registry, self::ISSUER);

        $server = $resolver->resolve(Request::create('https://pimcore.example.com/pimcore-mcp/studio/product-read'));
        $other = $resolver->resolve(Request::create('https://pimcore.example.com/pimcore-mcp/agent/content'));

        $this->assertSame(self::PRODUCT_READ, $server?->canonicalUri);
        $this->assertSame(self::ISSUER . '/pimcore-mcp', $other?->canonicalUri);
    }

    /**
     * An operator's `oauth.resources` entry for the same URI wins over the contributed
     * one, as for every provider.
     */
    public function testAConfiguredEntryForTheSameUriWins(): void
    {
        $repository = $this->makeEmpty(McpServerConfigRepositoryInterface::class, [
            'list' => [$this->server('product-read', ['read-tool', 'write-tool'])],
        ]);
        $registry = new ConfigProtectedResourceRegistry(
            [['uri' => self::PRODUCT_READ, 'scopes_supported' => ['mcp:read']]],
            [new McpServerResourceProvider($repository, $this->toolRegistry(), true, self::ISSUER)],
        );

        $this->assertSame(['mcp:read'], $registry->get(self::PRODUCT_READ)?->scopesSupported);
    }

    /**
     * @param list<McpServerDefinition> $servers
     *
     * @return list<ProtectedResource>
     */
    private function resources(array $servers, bool $enabled = true, ?string $issuer = self::ISSUER): array
    {
        $repository = $this->makeEmpty(McpServerConfigRepositoryInterface::class, ['list' => $servers]);

        return iterator_to_array(
            (new McpServerResourceProvider($repository, $this->toolRegistry(), $enabled, $issuer))->resources(),
            false,
        );
    }

    /**
     * @param list<string> $tools
     * @param list<string> $scopes
     */
    private function server(string $slug, array $tools, bool $enabled = true, array $scopes = []): McpServerDefinition
    {
        return McpServerDefinition::fromArray($slug, [
            'url_slug' => $slug,
            'tools' => $tools,
            'scopes' => $scopes,
            'enabled' => $enabled,
        ]);
    }

    private function toolRegistry(): McpToolRegistryInterface
    {
        $tools = [
            'read-tool' => $this->tool('read-tool', true),
            'write-tool' => $this->tool('write-tool', false),
        ];

        return $this->makeEmpty(McpToolRegistryInterface::class, [
            'get' => static fn (string $name): ?McpToolReference => $tools[$name] ?? null,
        ]);
    }

    private function tool(string $name, bool $readOnly): McpToolReference
    {
        return new McpToolReference(
            $name,
            null,
            '',
            new ToolAnnotations(readOnlyHint: $readOnly),
            null,
            self::class,
            'handle',
        );
    }
}
