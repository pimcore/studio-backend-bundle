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
use Pimcore\Bundle\StudioBackendBundle\Mcp\McpPath;
use Pimcore\Bundle\StudioBackendBundle\Mcp\ProtectedResourceProvider;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Dto\ProtectedResource;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Registry\ConfigProtectedResourceRegistry;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Registry\ScopeRegistry;
use function iterator_to_array;

/**
 * @internal
 */
final class ProtectedResourceProviderTest extends Unit
{
    private const string ISSUER = 'https://pimcore.example.com';

    private const string MCP_RESOURCE = self::ISSUER . '/pimcore-mcp';

    public function testContributesTheMcpResourceWhenEnabled(): void
    {
        $resources = $this->resources(enabled: true);

        $this->assertCount(1, $resources);
        $this->assertSame(self::MCP_RESOURCE, $resources[0]->canonicalUri);
        $this->assertSame(['mcp:read', 'mcp:write'], $resources[0]->scopesSupported);
    }

    /**
     * The RFC 9728 document serialises this verbatim, and a client reads it to find where
     * to authenticate. An empty list leaves it with nowhere to go.
     */
    public function testNamesTheAuthorizationServer(): void
    {
        $resources = $this->resources(enabled: true);

        $this->assertSame([self::ISSUER], $resources[0]->authorizationServers);

        $metadata = $this->registry(enabled: true)->metadataFor(self::MCP_RESOURCE);
        $this->assertNotNull($metadata);
        $this->assertSame([self::ISSUER], $metadata->toArray()['authorization_servers']);
    }

    public function testContributesNothingWhenDisabled(): void
    {
        $this->assertSame([], $this->resources(enabled: false));
        $this->assertSame([], $this->registry(enabled: false)->all());
    }

    /**
     * Fails closed: with no configured issuer there is no trustworthy base to build the
     * URI from, and an unregistered resource is refused at the authorization endpoint.
     */
    public function testContributesNothingWithoutAnIssuer(): void
    {
        $this->assertSame([], $this->resources(enabled: true, issuer: null));
    }

    /**
     * Regression, HIGH severity. `Host` is caller-supplied unless `trusted_hosts` is
     * configured, and it is empty by default. Deriving the resource URI from the request
     * let an attacker declare their own host as a protected resource, obtain a token
     * stamped with it, and pass the audience check by replaying the same spoofed header -
     * the check compared the attacker's string against the attacker's own string.
     *
     * Post-refactor this is structural rather than a matter of which value is read: a
     * provider is handed no request at all, so there is nothing to spoof.
     */
    public function testUriComesFromConfigurationAndNotFromAnyRequest(): void
    {
        $this->assertSame(self::MCP_RESOURCE, $this->resources(enabled: true)[0]->canonicalUri);

        $registry = $this->registry(enabled: true);
        $this->assertFalse($registry->has('http://evil.example/pimcore-mcp'));
        $this->assertTrue($registry->has(self::MCP_RESOURCE));
        $this->assertCount(1, $registry->all());
    }

    /**
     * The audience the MCP authenticator enforces has to be one that was actually
     * contributed, or `validatedResource()` refuses the authorization request. Both sides
     * read McpPath::BASE and the configured issuer, so they agree by construction.
     */
    public function testUriIsTheOneTheAuthenticatorEnforces(): void
    {
        $this->assertTrue($this->registry(enabled: true)->has(self::ISSUER . McpPath::BASE));
    }

    /**
     * An operator who declares the same URI under `oauth.resources` has made a deliberate
     * choice, most often to narrow the scopes. The contributed default must not win over
     * it, and must not add a second resource carrying the wider set.
     */
    public function testConfiguredResourceForTheSameUriWins(): void
    {
        $registry = new ConfigProtectedResourceRegistry(
            [['uri' => self::MCP_RESOURCE, 'scopes_supported' => ['mcp:read']]],
            [new ProtectedResourceProvider(true, self::ISSUER)],
        );

        $resource = $registry->get(self::MCP_RESOURCE);
        $this->assertNotNull($resource);
        $this->assertSame(['mcp:read'], $resource->scopesSupported);
        $this->assertCount(1, $registry->all());
    }

    /**
     * A proxy presenting a different host used to produce a second registration with the
     * built-in scopes, silently widening an operator's narrowed configuration. Nothing
     * reads a host any more, so the situation cannot arise.
     */
    public function testAnOperatorsNarrowedScopesAreNotWidened(): void
    {
        $registry = new ConfigProtectedResourceRegistry(
            [['uri' => self::MCP_RESOURCE, 'scopes_supported' => ['mcp:read']]],
            [new ProtectedResourceProvider(true, self::ISSUER)],
        );

        $this->assertSame(['mcp:read'], (new ScopeRegistry($registry))->all());
    }

    /**
     * The end the whole change is for: the scope catalogue picks the MCP scopes up from
     * the resource, with no separate provider declaring them.
     */
    public function testScopeCatalogueGainsTheMcpScopes(): void
    {
        $this->assertSame(['mcp:read', 'mcp:write'], (new ScopeRegistry($this->registry(enabled: true)))->all());
        $this->assertSame([], (new ScopeRegistry($this->registry(enabled: false)))->all());
    }

    /**
     * @return list<ProtectedResource>
     */
    private function resources(bool $enabled, ?string $issuer = self::ISSUER): array
    {
        return iterator_to_array(
            (new ProtectedResourceProvider($enabled, $issuer))->resources(),
            false,
        );
    }

    private function registry(bool $enabled, ?string $issuer = self::ISSUER): ConfigProtectedResourceRegistry
    {
        return new ConfigProtectedResourceRegistry([], [new ProtectedResourceProvider($enabled, $issuer)]);
    }
}
