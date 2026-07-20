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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\OAuth\Server;

use Codeception\Test\Unit;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Server\Entity\ClientEntity;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Server\Repository\ClientRepository;

final class ClientRepositoryTest extends Unit
{
    private function repository(): ClientRepository
    {
        return new ClientRepository([
            'studio-mcp' => [
                'name' => 'Studio MCP',
                'redirect_uris' => ['https://localhost/callback'],
                'confidential' => true,
                'secret' => 'top-secret',
                'service_user' => 21,
            ],
            'public-client' => [
                'name' => 'Public Client',
                'redirect_uris' => ['https://localhost/cb'],
                'confidential' => false,
            ],
        ]);
    }

    public function testResolvesClientEntityWithServiceUser(): void
    {
        $client = $this->repository()->getClientEntity('studio-mcp');
        $this->assertInstanceOf(ClientEntity::class, $client);
        $this->assertSame('studio-mcp', $client->getIdentifier());
        $this->assertTrue($client->isConfidential());
        $this->assertSame(21, $client->getServiceUserId());
    }

    public function testUnknownClientIsNull(): void
    {
        $this->assertNull($this->repository()->getClientEntity('nope'));
    }

    public function testConfidentialClientRequiresMatchingSecret(): void
    {
        $repo = $this->repository();
        $this->assertTrue($repo->validateClient('studio-mcp', 'top-secret', 'client_credentials'));
        $this->assertFalse($repo->validateClient('studio-mcp', 'wrong', 'client_credentials'));
        $this->assertFalse($repo->validateClient('studio-mcp', null, 'client_credentials'));
    }

    public function testPublicClientNeedsNoSecret(): void
    {
        $this->assertTrue($this->repository()->validateClient('public-client', null, 'authorization_code'));
    }

    public function testPublicClientCannotUseClientCredentials(): void
    {
        // A public client has no secret and must not obtain a service-account
        // token via client_credentials with only its client id.
        $this->assertFalse($this->repository()->validateClient('public-client', null, 'client_credentials'));
    }

    public function testUnknownClientDoesNotValidate(): void
    {
        $this->assertFalse($this->repository()->validateClient('nope', 'x', 'client_credentials'));
    }
}
