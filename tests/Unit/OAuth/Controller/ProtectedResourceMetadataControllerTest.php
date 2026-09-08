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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\OAuth\Controller;

use Codeception\Test\Unit;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Controller\ProtectedResourceMetadataController;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Dto\ProtectedResource;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Registry\ConfigProtectedResourceRegistry;
use Symfony\Component\HttpFoundation\Request;
use function json_decode;
use Symfony\Component\HttpFoundation\Response;

final class ProtectedResourceMetadataControllerTest extends Unit
{
    public function testServesMetadataForRegisteredResource(): void
    {
        $registry = new ConfigProtectedResourceRegistry();
        $registry->register(new ProtectedResource(
            'https://pimcore.example.com/pimcore-mcp',
            ['mcp:read'],
            ['https://pimcore.example.com/pimcore-oauth'],
        ));

        $response = (new ProtectedResourceMetadataController($registry))(
            $this->requestFor('https://pimcore.example.com'),
            '/pimcore-mcp',
        );

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertSame(
            [
                'resource' => 'https://pimcore.example.com/pimcore-mcp',
                'authorization_servers' => ['https://pimcore.example.com/pimcore-oauth'],
                'scopes_supported' => ['mcp:read'],
                'bearer_methods_supported' => ['header'],
            ],
            json_decode((string) $response->getContent(), true),
        );
    }

    public function testReturns404ForUnknownResource(): void
    {
        $response = (new ProtectedResourceMetadataController(new ConfigProtectedResourceRegistry()))(
            $this->requestFor('https://pimcore.example.com'),
            '/pimcore-mcp',
        );

        $this->assertSame(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }

    /**
     * The other half of the same problem as the 401 challenge: the document has to be
     * findable on whatever host the client reaches it by, because the resource it
     * describes is registered under the issuer.
     */
    public function testResolvesByIssuerWhenTheRequestArrivesOnAnotherHost(): void
    {
        $registry = new ConfigProtectedResourceRegistry([
            [
                'uri' => 'https://pimcore.example.com/pimcore-mcp',
                'scopes_supported' => ['mcp:read'],
                'authorization_servers' => ['https://pimcore.example.com'],
            ],
        ]);

        $response = (new ProtectedResourceMetadataController($registry, 'https://pimcore.example.com'))(
            Request::create('http://internal.local/.well-known/oauth-protected-resource/pimcore-mcp'),
            '/pimcore-mcp',
        );

        $this->assertSame(200, $response->getStatusCode());

        $payload = json_decode((string) $response->getContent(), true);

        $this->assertIsArray($payload);
        $this->assertSame('https://pimcore.example.com/pimcore-mcp', $payload['resource'] ?? null);
    }

    private function requestFor(string $schemeAndHost): Request
    {
        return Request::create($schemeAndHost . '/.well-known/oauth-protected-resource/pimcore-mcp');
    }
}
