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
    public function testEnabledEmitsChallengeWithResourceMetadata(): void
    {
        // Nothing registered covers the endpoint, so the challenge falls back to the
        // request path (the resource for the endpoint that was actually requested).
        $response = $this->entryPoint(true)->start($this->mcpRequest());

        $this->assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
        $this->assertSame(
            'Bearer resource_metadata='
            . '"https://pimcore.example.com/.well-known/oauth-protected-resource/pimcore-mcp/message",'
            . ' scope="mcp:read"',
            $response->headers->get('WWW-Authenticate'),
        );
    }

    public function testDisabledReturnsPlain401WithoutChallenge(): void
    {
        $response = $this->entryPoint(false)->start($this->mcpRequest());

        $this->assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
        $this->assertFalse($response->headers->has('WWW-Authenticate'));
    }

    /**
     * Behind a proxy the request host is not the issuer, and the resource is registered
     * under the issuer. Pointing the client at the request host would send it to a
     * metadata document that resolves to nothing, so discovery would never start.
     */
    public function testChallengeUsesTheIssuerRatherThanTheRequestHost(): void
    {
        $entryPoint = $this->entryPoint(true, null, 'https://pimcore.example.com');

        $response = $entryPoint->start(Request::create('http://internal.local/pimcore-mcp/message'));

        $this->assertSame(
            'Bearer resource_metadata='
            . '"https://pimcore.example.com/.well-known/oauth-protected-resource/pimcore-mcp/message",'
            . ' scope="mcp:read"',
            $response->headers->get('WWW-Authenticate'),
        );
    }

    /**
     * When a broader resource covers the endpoint, the authenticator validates the token
     * against that resource, so the challenge must advertise the matched resource's
     * metadata — not the request path, which the metadata controller's exact lookup would
     * answer with a 404.
     */
    public function testChallengePointsAtTheMatchedResourceNotTheRequestPath(): void
    {
        $matched = new ProtectedResource('https://pimcore.example.com/pimcore-mcp', [], []);

        $response = $this->entryPoint(true, $matched, 'https://pimcore.example.com')
            ->start(Request::create('https://pimcore.example.com/pimcore-mcp/agent/content'));

        $this->assertSame(
            'Bearer resource_metadata='
            . '"https://pimcore.example.com/.well-known/oauth-protected-resource/pimcore-mcp",'
            . ' scope="mcp:read"',
            $response->headers->get('WWW-Authenticate'),
        );
    }

    private function entryPoint(bool $enabled, ?ProtectedResource $match = null, ?string $issuer = null): McpAuthenticationEntryPoint
    {
        $resolver = $this->makeEmpty(RequestResourceResolverInterface::class, ['resolve' => $match]);

        return new McpAuthenticationEntryPoint($enabled, $resolver, $issuer);
    }

    private function mcpRequest(): Request
    {
        return Request::create('https://pimcore.example.com/pimcore-mcp/message');
    }
}
