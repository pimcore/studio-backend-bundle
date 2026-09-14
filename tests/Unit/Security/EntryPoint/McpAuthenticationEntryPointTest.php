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
use Pimcore\Bundle\StudioBackendBundle\Security\Authenticator\Mcp\OAuthAccessTokenAuthenticator;
use Pimcore\Bundle\StudioBackendBundle\Security\EntryPoint\McpAuthenticationEntryPoint;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class McpAuthenticationEntryPointTest extends Unit
{
    private const string EXPECTED_CHALLENGE =
        'Bearer resource_metadata='
        . '"https://pimcore.example.com/.well-known/oauth-protected-resource/pimcore-mcp",'
        . ' scope="mcp:read"';

    public function testEnabledEmitsChallengeWithResourceMetadata(): void
    {
        $response = (new McpAuthenticationEntryPoint(true))->start($this->mcpRequest());

        $this->assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
        $this->assertSame(self::EXPECTED_CHALLENGE, $response->headers->get('WWW-Authenticate'));
    }

    /**
     * The challenge must name the resource the token is validated against, not the
     * endpoint that produced the 401. MCP endpoints are sub-paths of the base while
     * OAuthAccessTokenAuthenticator validates all of them against the base, so deriving
     * the URL from the request path advertised metadata for an unregistered resource -
     * which the RFC 9728 endpoint answers with 404, breaking discovery.
     */
    public function testChallengeNamesTheBaseResourceForEverySubPath(): void
    {
        $entryPoint = new McpAuthenticationEntryPoint(true);

        foreach (['/pimcore-mcp/message', '/pimcore-mcp/agent/pimcore-data-objects-read'] as $path) {
            $response = $entryPoint->start(Request::create('https://pimcore.example.com' . $path));

            $this->assertSame(self::EXPECTED_CHALLENGE, $response->headers->get('WWW-Authenticate'));
        }
    }

    public function testChallengeUsesTheResourcePathTheAuthenticatorEnforces(): void
    {
        $response = (new McpAuthenticationEntryPoint(true))->start($this->mcpRequest());

        $this->assertStringContainsString(
            '/.well-known/oauth-protected-resource' . OAuthAccessTokenAuthenticator::MCP_RESOURCE_PATH . '"',
            (string) $response->headers->get('WWW-Authenticate'),
        );
    }

    public function testDisabledReturnsPlain401WithoutChallenge(): void
    {
        $response = (new McpAuthenticationEntryPoint(false))->start($this->mcpRequest());

        $this->assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
        $this->assertFalse($response->headers->has('WWW-Authenticate'));
    }

    private function mcpRequest(): Request
    {
        return Request::create('https://pimcore.example.com/pimcore-mcp/message');
    }
}
