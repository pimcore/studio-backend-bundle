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
    private const string HOST = 'https://pimcore.example.com';

    private const string MCP_RESOURCE = self::HOST . '/pimcore-mcp';

    public function testRegistersTheMcpResourceWhenEnabled(): void
    {
        $registry = new ConfigProtectedResourceRegistry();
        $this->dispatch($registry, enabled: true);

        $resource = $registry->get(self::MCP_RESOURCE);
        $this->assertNotNull($resource);
        $this->assertSame(self::MCP_RESOURCE, $resource->canonicalUri);
        $this->assertSame(['mcp:read', 'mcp:write'], $resource->scopesSupported);
    }

    public function testRegistersNothingWhenDisabled(): void
    {
        $registry = new ConfigProtectedResourceRegistry();
        $this->dispatch($registry, enabled: false);

        $this->assertSame([], $registry->all());
        $this->assertFalse($registry->has(self::MCP_RESOURCE));
    }

    /**
     * The URI must be the one the MCP authenticator validates a token's audience
     * against. Deriving both from the request host is what keeps them identical; a
     * mismatch would refuse valid tokens with nothing saying why.
     */
    public function testUriMatchesWhatTheAuthenticatorEnforces(): void
    {
        $registry = new ConfigProtectedResourceRegistry();
        $this->dispatch($registry, enabled: true);

        $this->assertTrue(
            $registry->has(self::HOST . OAuthAccessTokenAuthenticator::MCP_RESOURCE_PATH),
        );
    }

    public function testUriFollowsTheRequestHost(): void
    {
        $registry = new ConfigProtectedResourceRegistry();
        $this->dispatch($registry, enabled: true, url: 'https://other.example/pimcore-mcp/agent/x');

        $this->assertTrue($registry->has('https://other.example/pimcore-mcp'));
        $this->assertFalse($registry->has(self::MCP_RESOURCE));
    }

    /**
     * An operator who declares the same URI under `oauth.resources` has made a
     * deliberate choice, most often to change the scopes. The built-in default must
     * not overwrite it.
     */
    public function testOperatorConfiguredResourceForTheSameUriWins(): void
    {
        $registry = new ConfigProtectedResourceRegistry([
            [
                'uri' => self::MCP_RESOURCE,
                'scopes_supported' => ['mcp:read'],
                'authorization_servers' => [self::HOST],
            ],
        ]);

        $this->dispatch($registry, enabled: true);

        $resource = $registry->get(self::MCP_RESOURCE);
        $this->assertNotNull($resource);
        $this->assertSame(['mcp:read'], $resource->scopesSupported);
        $this->assertSame([self::HOST], $resource->authorizationServers);
        $this->assertCount(1, $registry->all());
    }

    /**
     * The override is matched on the canonical form, so a trailing slash or a
     * differently-cased host in configuration is still the same resource.
     */
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
            $this->dispatch($registry, enabled: true, url: self::HOST . $path);

            $this->assertTrue($registry->has(self::MCP_RESOURCE), $path . ' must still register.');
        }
    }

    public function testSubRequestIsIgnored(): void
    {
        $registry = new ConfigProtectedResourceRegistry();
        $this->dispatch($registry, enabled: true, mainRequest: false);

        $this->assertSame([], $registry->all());
    }

    public function testRunsBeforeTheFirewallAndTheRouter(): void
    {
        $events = McpProtectedResourceSubscriber::getSubscribedEvents();

        $this->assertArrayHasKey(KernelEvents::REQUEST, $events);
        [$method, $priority] = $events[KernelEvents::REQUEST];
        $this->assertSame('onKernelRequest', $method);
        // RouterListener is 32 and the firewall 8; both read the registry downstream.
        $this->assertGreaterThan(32, $priority);
    }

    /**
     * The end the whole change is for: the scope catalogue picks the MCP scopes up
     * from the resource, with no separate provider declaring them.
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
        string $url = self::HOST . '/pimcore-mcp/agent/documents',
        bool $mainRequest = true,
    ): void {
        $subscriber = new McpProtectedResourceSubscriber($registry, $enabled);

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
