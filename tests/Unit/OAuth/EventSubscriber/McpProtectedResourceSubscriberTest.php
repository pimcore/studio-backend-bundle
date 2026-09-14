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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\OAuth\EventSubscriber;

use Codeception\Test\Unit;
use Pimcore\Bundle\StudioBackendBundle\OAuth\EventSubscriber\McpProtectedResourceSubscriber;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Registry\ConfigProtectedResourceRegistry;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Registry\ScopeRegistry;
use Pimcore\Bundle\StudioBackendBundle\Security\Authenticator\Mcp\OAuthAccessTokenAuthenticator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * @internal
 */
final class McpProtectedResourceSubscriberTest extends Unit
{
    private const string ISSUER = 'https://pimcore.example.com';

    private const string MCP_RESOURCE = self::ISSUER . '/pimcore-mcp';

    public function testRegistersTheMcpResourceWhenEnabled(): void
    {
        $registry = new ConfigProtectedResourceRegistry();
        $this->dispatch($registry, enabled: true);

        $resource = $registry->get(self::MCP_RESOURCE);
        $this->assertNotNull($resource);
        $this->assertSame(self::MCP_RESOURCE, $resource->canonicalUri);
        $this->assertSame(['mcp:read', 'mcp:write'], $resource->scopesSupported);
    }

    /**
     * The RFC 9728 document serialises this verbatim, and a client reads it to find
     * where to authenticate. An empty list leaves it with nowhere to go.
     */
    public function testMetadataNamesTheAuthorizationServer(): void
    {
        $registry = new ConfigProtectedResourceRegistry();
        $this->dispatch($registry, enabled: true);

        $resource = $registry->get(self::MCP_RESOURCE);
        $this->assertNotNull($resource);
        $this->assertSame([self::ISSUER], $resource->authorizationServers);

        $metadata = $registry->metadataFor(self::MCP_RESOURCE);
        $this->assertNotNull($metadata);
        $this->assertSame([self::ISSUER], $metadata->toArray()['authorization_servers']);
    }

    public function testRegistersNothingWhenDisabled(): void
    {
        $registry = new ConfigProtectedResourceRegistry();
        $this->dispatch($registry, enabled: false);

        $this->assertSame([], $registry->all());
    }

    /**
     * Fails closed: with no configured issuer there is no trustworthy base to build the
     * URI from, and an unregistered resource is refused at the authorization endpoint.
     */
    public function testRegistersNothingWithoutAnIssuer(): void
    {
        $registry = new ConfigProtectedResourceRegistry();
        $this->dispatch($registry, enabled: true, issuer: null);

        $this->assertSame([], $registry->all());
    }

    /**
     * Regression, HIGH severity. `Host` is caller-supplied unless `trusted_hosts` is
     * configured, and it is empty by default. Deriving the resource URI from it let an
     * attacker register their own host as a protected resource, obtain a token stamped
     * with it, and pass the audience check by replaying the same spoofed header - the
     * check compared the attacker's string against the attacker's string.
     */
    public function testSpoofedHostIsNotRegistered(): void
    {
        $registry = new ConfigProtectedResourceRegistry();
        $this->dispatch($registry, enabled: true, url: 'http://evil.example/pimcore-mcp/agent/x');

        $this->assertFalse($registry->has('http://evil.example/pimcore-mcp'));
        $this->assertTrue($registry->has(self::MCP_RESOURCE));
        $this->assertCount(1, $registry->all());
    }

    /**
     * The audience the MCP authenticator enforces has to be one that was actually
     * registered, or `validatedResource()` refuses the authorization request. Pinning
     * both to the configured issuer is what keeps them equal without either following
     * the caller.
     */
    public function testRegisteredUriIsTheOneTheAuthenticatorEnforces(): void
    {
        $registry = new ConfigProtectedResourceRegistry();
        $this->dispatch($registry, enabled: true, url: 'http://evil.example/pimcore-mcp');

        $this->assertTrue(
            $registry->has(self::ISSUER . OAuthAccessTokenAuthenticator::MCP_RESOURCE_PATH),
        );
    }

    public function testUriDoesNotFollowTheRequestHost(): void
    {
        $registry = new ConfigProtectedResourceRegistry();
        $this->dispatch($registry, enabled: true, url: 'https://other.example/pimcore-mcp/agent/x');

        $this->assertTrue($registry->has(self::MCP_RESOURCE));
        $this->assertFalse($registry->has('https://other.example/pimcore-mcp'));
    }

    /**
     * An operator who declares the same URI under `oauth.resources` has made a
     * deliberate choice, most often to narrow the scopes. The built-in default must not
     * overwrite it, and must not add a second resource carrying the wider set.
     */
    public function testOperatorConfiguredResourceForTheSameUriWins(): void
    {
        $registry = new ConfigProtectedResourceRegistry([
            [
                'uri' => self::MCP_RESOURCE,
                'scopes_supported' => ['mcp:read'],
                'authorization_servers' => [self::ISSUER],
            ],
        ]);

        $this->dispatch($registry, enabled: true);

        $resource = $registry->get(self::MCP_RESOURCE);
        $this->assertNotNull($resource);
        $this->assertSame(['mcp:read'], $resource->scopesSupported);
        $this->assertCount(1, $registry->all());
    }

    public function testOperatorOverrideIsMatchedCanonically(): void
    {
        $registry = new ConfigProtectedResourceRegistry([
            ['uri' => 'https://PIMCORE.example.com/pimcore-mcp/', 'scopes_supported' => ['mcp:read']],
        ]);

        $this->dispatch($registry, enabled: true);

        $this->assertCount(1, $registry->all());
        $resource = $registry->get(self::MCP_RESOURCE);
        $this->assertNotNull($resource);
        $this->assertSame(['mcp:read'], $resource->scopesSupported);
    }

    /**
     * A proxy presenting a different host used to produce a second registration with the
     * built-in scopes, silently widening an operator's narrowed configuration. With the
     * URI pinned to the issuer the request host is irrelevant, so it cannot happen.
     */
    public function testProxyHostMismatchDoesNotWidenAnOperatorsScopes(): void
    {
        $registry = new ConfigProtectedResourceRegistry([
            ['uri' => self::MCP_RESOURCE, 'scopes_supported' => ['mcp:read']],
        ]);
        $scopes = new ScopeRegistry($registry);

        $this->dispatch($registry, enabled: true, url: 'http://internal.lan/pimcore-mcp/agent/x');

        $this->assertCount(1, $registry->all());
        $this->assertSame(['mcp:read'], $scopes->all());
    }

    public function testRunsOnNonMcpPathsToo(): void
    {
        // The metadata document and the authorize endpoint both consult the registry
        // while on paths that belong to no MCP surface.
        foreach (
            [
                '/.well-known/oauth-protected-resource/pimcore-mcp',
                '/pimcore-oauth/authorize',
            ] as $path
        ) {
            $registry = new ConfigProtectedResourceRegistry();
            $this->dispatch($registry, enabled: true, url: self::ISSUER . $path);

            $this->assertTrue($registry->has(self::MCP_RESOURCE), $path . ' must still register.');
        }
    }

    public function testSubRequestIsIgnored(): void
    {
        $registry = new ConfigProtectedResourceRegistry();
        $this->dispatch($registry, enabled: true, mainRequest: false);

        $this->assertSame([], $registry->all());
    }

    public function testRunsBeforeTheEndpointGuardTheRouterAndTheFirewall(): void
    {
        $events = McpProtectedResourceSubscriber::getSubscribedEvents();

        $this->assertArrayHasKey(KernelEvents::REQUEST, $events);
        [$method, $priority] = $events[KernelEvents::REQUEST];
        $this->assertSame('onKernelRequest', $method);
        // Above OAuthEndpointGuardSubscriber (251), which is itself above the router
        // (32) and the firewall (8); every one of those reads the registry downstream.
        $this->assertGreaterThan(251, $priority);
    }

    /**
     * The end the whole change is for: the scope catalogue picks the MCP scopes up from
     * the resource, with no separate provider declaring them.
     */
    public function testScopeCatalogueGainsTheMcpScopes(): void
    {
        $registry = new ConfigProtectedResourceRegistry();
        $scopes = new ScopeRegistry($registry);

        $this->assertSame([], $scopes->all());

        $this->dispatch($registry, enabled: true);

        $this->assertSame(['mcp:read', 'mcp:write'], $scopes->all());
    }

    private function dispatch(
        ConfigProtectedResourceRegistry $registry,
        bool $enabled,
        string $url = self::ISSUER . '/pimcore-mcp/agent/documents',
        bool $mainRequest = true,
        ?string $issuer = self::ISSUER,
    ): void {
        $subscriber = new McpProtectedResourceSubscriber($registry, $enabled, $issuer);

        $subscriber->onKernelRequest(new RequestEvent(
            $this->kernel(),
            Request::create($url),
            $mainRequest ? HttpKernelInterface::MAIN_REQUEST : HttpKernelInterface::SUB_REQUEST,
        ));
    }

    private function kernel(): HttpKernelInterface
    {
        return new class implements HttpKernelInterface {
            public function handle(Request $request, int $type = self::MAIN_REQUEST, bool $catch = true): Response
            {
                return new Response();
            }
        };
    }
}
