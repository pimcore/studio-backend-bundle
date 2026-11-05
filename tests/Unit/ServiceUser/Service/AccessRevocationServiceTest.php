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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\ServiceUser\Service;

use Codeception\Stub\Expected;
use Codeception\Test\Unit;
use Pimcore\Bundle\StudioBackendBundle\Mcp\Dto\McpServerAccess;
use Pimcore\Bundle\StudioBackendBundle\Mcp\Dto\McpServerAccessEntry;
use Pimcore\Bundle\StudioBackendBundle\Mcp\Dto\McpServerDefinition;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Dto\DynamicClient;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\McpAccessTokenServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\ServiceUser\Service\AccessRevocationService;
use Pimcore\Bundle\StudioBackendBundle\Tests\Support\Fixture\InMemoryMcpServerConfigRepository;
use Pimcore\Bundle\StudioBackendBundle\Tests\Support\Fixture\InMemoryOAuthClientStore;
use Pimcore\Bundle\StudioBackendBundle\Tests\Support\Fixture\InMemoryOAuthTokenRecordStore;
use Pimcore\Model\User\ServiceUser;
use Psr\Log\NullLogger;

/**
 * @internal
 */
final class AccessRevocationServiceTest extends Unit
{
    private InMemoryOAuthClientStore $clients;

    private InMemoryOAuthTokenRecordStore $tokens;

    public function _before(): void
    {
        $this->clients = new InMemoryOAuthClientStore();
        $this->tokens = new InMemoryOAuthTokenRecordStore();
    }

    public function testDeletesTheBoundClient(): void
    {
        $this->clients->save($this->boundClient('svc_a', 7));
        $this->clients->save($this->boundClient('svc_b', 8));

        $this->service(new InMemoryMcpServerConfigRepository())->revokeAll($this->serviceUser(7, 'svc-erp-sync'));

        $this->assertNull($this->clients->findByServiceUserId(7));
        $this->assertNotNull($this->clients->findByServiceUserId(8), 'another service user keeps its client');
    }

    public function testRevokesTheUsersOAuthTokensByJti(): void
    {
        $this->tokens->persist('jti-1', 'access_token', 2000000000, 7, 'svc_a');
        $this->tokens->persist('jti-2', 'access_token', 2000000000, 7, 'svc_a');
        $this->tokens->persist('jti-3', 'access_token', 2000000000, 8, 'svc_b');

        $this->service(new InMemoryMcpServerConfigRepository())->revokeAll($this->serviceUser(7, 'svc-erp-sync'));

        $this->assertTrue($this->tokens->isRevoked('jti-1'));
        $this->assertTrue($this->tokens->isRevoked('jti-2'));
        $this->assertFalse($this->tokens->isRevoked('jti-3'));
    }

    public function testRevokesTheUsersMcpAccessTokens(): void
    {
        $mcpTokens = $this->makeEmpty(McpAccessTokenServiceInterface::class, [
            'revokeByUser' => Expected::once(function (int $userId): void {
                $this->assertSame(7, $userId);
            }),
        ]);

        $service = new AccessRevocationService(
            $this->clients,
            $this->tokens,
            $mcpTokens,
            new InMemoryMcpServerConfigRepository(),
            new NullLogger(),
        );
        $service->revokeAll($this->serviceUser(7, 'svc-erp-sync'));
    }

    public function testRemovesTheUsersEntriesFromEveryManagedServer(): void
    {
        $repository = new InMemoryMcpServerConfigRepository([
            $this->server('catalog', [
                new McpServerAccessEntry('svc-erp-sync', canRead: true, canAccess: true),
                new McpServerAccessEntry('jane', canRead: true, canAccess: true),
            ], [new McpServerAccessEntry('svc-erp-sync', canAccess: true)]),
            $this->server('assets', [new McpServerAccessEntry('svc-erp-sync', canAccess: true, canEdit: true)]),
            $this->server('untouched', [new McpServerAccessEntry('jane', canAccess: true)]),
        ]);

        $this->service($repository)->revokeAll($this->serviceUser(7, 'svc-erp-sync'));

        $catalog = $repository->get('catalog')->access;
        $this->assertSame(['jane'], $this->names($catalog->sharedUsers));
        $this->assertSame(
            ['svc-erp-sync'],
            $this->names($catalog->sharedRoles),
            'a role that happens to share the name is a different principal'
        );
        $this->assertSame([], $repository->get('assets')->access->sharedUsers);
        $this->assertSame(['jane'], $this->names($repository->get('untouched')->access->sharedUsers));
    }

    public function testKeepsTheRestOfTheServerDefinition(): void
    {
        $repository = new InMemoryMcpServerConfigRepository([
            new McpServerDefinition(
                id: 'catalog',
                displayName: 'Catalog',
                description: 'Products',
                toolIds: ['ping'],
                scopes: ['mcp:read'],
                enabled: false,
                access: new McpServerAccess(
                    owner: 'jane',
                    shareGlobal: true,
                    sharedUsers: [new McpServerAccessEntry('svc-erp-sync', canAccess: true)],
                ),
            ),
        ]);

        $this->service($repository)->revokeAll($this->serviceUser(7, 'svc-erp-sync'));

        $catalog = $repository->get('catalog');
        $this->assertSame('Catalog', $catalog->displayName);
        $this->assertSame('Products', $catalog->description);
        $this->assertSame(['ping'], $catalog->toolIds);
        $this->assertSame(['mcp:read'], $catalog->scopes);
        $this->assertFalse($catalog->enabled);
        $this->assertSame('jane', $catalog->access->owner);
        $this->assertTrue($catalog->access->shareGlobal);
    }

    public function testClearsOwnershipHeldByTheServiceUser(): void
    {
        $repository = new InMemoryMcpServerConfigRepository([
            new McpServerDefinition('catalog', 'Catalog', '', [], [], true, new McpServerAccess(owner: 'svc-erp-sync')),
        ]);

        $this->service($repository)->revokeAll($this->serviceUser(7, 'svc-erp-sync'));

        $this->assertNull($repository->get('catalog')->access->owner);
    }

    public function testAReadOnlyServerDoesNotStopTheRevocation(): void
    {
        $this->clients->save($this->boundClient('svc_a', 7));
        $repository = new InMemoryMcpServerConfigRepository([
            $this->server('shipped', [new McpServerAccessEntry('svc-erp-sync', canAccess: true)]),
            $this->server('managed', [new McpServerAccessEntry('svc-erp-sync', canAccess: true)]),
        ], ['shipped']);

        $this->service($repository)->revokeAll($this->serviceUser(7, 'svc-erp-sync'));

        $this->assertSame([], $repository->get('managed')->access->sharedUsers);
        $this->assertCount(1, $repository->get('shipped')->access->sharedUsers);
        $this->assertNull($this->clients->findByServiceUserId(7));
    }

    private function service(InMemoryMcpServerConfigRepository $repository): AccessRevocationService
    {
        return new AccessRevocationService(
            $this->clients,
            $this->tokens,
            $this->makeEmpty(McpAccessTokenServiceInterface::class),
            $repository,
            new NullLogger(),
        );
    }

    /**
     * @param list<McpServerAccessEntry> $users
     * @param list<McpServerAccessEntry> $roles
     */
    private function server(string $id, array $users, array $roles = []): McpServerDefinition
    {
        return new McpServerDefinition($id, $id, '', [], [], true, new McpServerAccess(
            sharedUsers: $users,
            sharedRoles: $roles,
        ));
    }

    /**
     * @param list<McpServerAccessEntry> $entries
     *
     * @return list<string>
     */
    private function names(array $entries): array
    {
        $names = [];
        foreach ($entries as $entry) {
            $names[] = $entry->name;
        }

        return $names;
    }

    private function boundClient(string $clientId, int $serviceUserId): DynamicClient
    {
        return new DynamicClient(
            $clientId,
            'client',
            [],
            ['client_credentials'],
            [],
            true,
            'hash',
            serviceUserId: $serviceUserId,
        );
    }

    private function serviceUser(int $id, string $name): ServiceUser
    {
        $serviceUser = new ServiceUser();
        $serviceUser->setId($id);
        $serviceUser->setName($name);

        return $serviceUser;
    }
}
