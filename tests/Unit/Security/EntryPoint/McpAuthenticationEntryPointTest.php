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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\Security\EntryPoint;

use Codeception\Test\Unit;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Dto\ProtectedResource;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Resolver\RequestResourceResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Security\EntryPoint\McpAuthenticationEntryPoint;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class McpAuthenticationEntryPointTest extends Unit
{
    private const string METADATA = 'https://pimcore.example.com/.well-known/oauth-protected-resource';

    private const string BASE_CHALLENGE = 'Bearer resource_metadata="' . self::METADATA . '/pimcore-mcp"';

    public function testEnabledEmitsChallengeWithResourceMetadata(): void
    {
        $base = new ProtectedResource('https://pimcore.example.com/pimcore-mcp', [], []);

        $response = $this->entryPoint(true, $base)->start($this->mcpRequest());

        $this->assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
        $this->assertSame(self::BASE_CHALLENGE, $response->headers->get('WWW-Authenticate'));
    }

    /**
     * The challenge must name the resource the token is validated against, not the
     * endpoint that produced the 401. An endpoint covered only by the base is validated
     * against the base, so deriving the URL from the request path would advertise
     * metadata for an unregistered resource - which the RFC 9728 endpoint answers with
     * 404, breaking discovery.
     */
    public function testChallengeNamesTheBaseResourceForEverySubPathItCovers(): void
    {
        $entryPoint = $this->entryPoint(true, new ProtectedResource('https://pimcore.example.com/pimcore-mcp', [], []));

        foreach (['/pimcore-mcp/message', '/pimcore-mcp/agent/pimcore-data-objects-read'] as $path) {
            $response = $entryPoint->start(Request::create('https://pimcore.example.com' . $path));

            $this->assertSame(self::BASE_CHALLENGE, $response->headers->get('WWW-Authenticate'));
        }
    }

    /**
     * A configured MCP server registers its own resource, and the authenticator binds
     * the token to it; the challenge has to send the client to that resource's metadata,
     * or the client would request a token for the base and be refused at the server.
     */
    public function testChallengeNamesThePerServerResourceWhenOneIsRegistered(): void
    {
        $server = new ProtectedResource('https://pimcore.example.com/pimcore-mcp/studio/product-read', [], []);

        $response = $this->entryPoint(true, $server)
            ->start(Request::create('https://pimcore.example.com/pimcore-mcp/studio/product-read'));

        $this->assertSame(
            'Bearer resource_metadata="' . self::METADATA . '/pimcore-mcp/studio/product-read"',
            $response->headers->get('WWW-Authenticate'),
        );
    }

    /**
     * Without a resolvable resource (no issuer, nothing registered) the challenge falls
     * back to the bundle's own base resource rather than the raw request path.
     */
    public function testChallengeFallsBackToTheBaseWhenNothingResolves(): void
    {
        $response = $this->entryPoint(true, null)
            ->start(Request::create('https://pimcore.example.com/pimcore-mcp/studio/unknown'));

        $this->assertSame(self::BASE_CHALLENGE, $response->headers->get('WWW-Authenticate'));
    }

    /**
     * The discovery URL is built from the host the client reached, on purpose: it is
     * where the client fetches metadata from, it authorizes nothing, and the metadata
     * controller resolves from the request as well. Behind a proxy, trusted_proxies makes
     * the forwarded host the request host.
     */
    public function testChallengeUsesTheRequestHost(): void
    {
        $base = new ProtectedResource('https://pimcore.example.com/pimcore-mcp', [], []);

        $response = $this->entryPoint(true, $base)->start(Request::create('https://mcp.example.org/pimcore-mcp/message'));

        $this->assertSame(
            'Bearer resource_metadata="https://mcp.example.org/.well-known/oauth-protected-resource/pimcore-mcp"',
            $response->headers->get('WWW-Authenticate'),
        );
    }

    /**
     * No `scope` parameter: the metadata document already advertises scopes_supported,
     * derived from the registry, and a hard-coded hint could contradict it.
     */
    public function testChallengeCarriesNoScopeParameter(): void
    {
        $server = new ProtectedResource('https://pimcore.example.com/pimcore-mcp/studio/product-read', ['mcp:read'], []);

        $response = $this->entryPoint(true, $server)->start($this->mcpRequest());

        $this->assertStringNotContainsString('scope=', (string) $response->headers->get('WWW-Authenticate'));
    }

    /**
     * An origin-only resource covers the request but has no path, so the challenge must
     * point at the resource's own metadata document (empty suffix).
     */
    public function testChallengePointsAtAMatchedRootResource(): void
    {
        $matched = new ProtectedResource('https://pimcore.example.com', [], []);

        $response = $this->entryPoint(true, $matched)->start($this->mcpRequest());

        $this->assertSame(
            'Bearer resource_metadata="' . self::METADATA . '"',
            $response->headers->get('WWW-Authenticate'),
        );
    }

    public function testDisabledReturnsPlain401WithoutChallenge(): void
    {
        $response = $this->entryPoint(false)->start($this->mcpRequest());

        $this->assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
        $this->assertFalse($response->headers->has('WWW-Authenticate'));
    }

    private function entryPoint(bool $enabled, ?ProtectedResource $match = null): McpAuthenticationEntryPoint
    {
        $resolver = $this->makeEmpty(RequestResourceResolverInterface::class, ['resolve' => $match]);

        return new McpAuthenticationEntryPoint($enabled, $resolver);
    }

    private function mcpRequest(): Request
    {
        return Request::create('https://pimcore.example.com/pimcore-mcp/message');
    }
}
