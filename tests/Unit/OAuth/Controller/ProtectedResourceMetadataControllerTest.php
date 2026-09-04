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

    private function requestFor(string $schemeAndHost): Request
    {
        return Request::create($schemeAndHost . '/.well-known/oauth-protected-resource/pimcore-mcp');
    }
}
