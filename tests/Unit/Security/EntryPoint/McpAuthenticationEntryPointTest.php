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
use Pimcore\Bundle\StudioBackendBundle\Security\EntryPoint\McpAuthenticationEntryPoint;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class McpAuthenticationEntryPointTest extends Unit
{
    public function testEnabledEmitsChallengeWithResourceMetadata(): void
    {
        $response = (new McpAuthenticationEntryPoint(true))->start($this->mcpRequest());

        $this->assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
        // The metadata URL is derived from the request path, so it points at the
        // protected resource for the endpoint that was actually requested.
        $this->assertSame(
            'Bearer resource_metadata='
            . '"https://pimcore.example.com/.well-known/oauth-protected-resource/pimcore-mcp/message",'
            . ' scope="mcp:read"',
            $response->headers->get('WWW-Authenticate'),
        );
    }

    public function testDisabledReturnsPlain401WithoutChallenge(): void
    {
        $response = (new McpAuthenticationEntryPoint(false))->start($this->mcpRequest());

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
        $entryPoint = new McpAuthenticationEntryPoint(true, 'https://pimcore.example.com');

        $response = $entryPoint->start(Request::create('http://internal.local/pimcore-mcp/message'));

        $this->assertSame(
            'Bearer resource_metadata='
            . '"https://pimcore.example.com/.well-known/oauth-protected-resource/pimcore-mcp/message",'
            . ' scope="mcp:read"',
            $response->headers->get('WWW-Authenticate'),
        );
    }

    private function mcpRequest(): Request
    {
        return Request::create('https://pimcore.example.com/pimcore-mcp/message');
    }
}
