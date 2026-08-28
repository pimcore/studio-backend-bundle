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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\Mcp\Service;

use Codeception\Test\Unit;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ElementExistsException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Mcp\Dto\McpServerAccess;
use Pimcore\Bundle\StudioBackendBundle\Mcp\Dto\McpServerAccessEntry;
use Pimcore\Bundle\StudioBackendBundle\Mcp\Dto\McpServerDefinition;
use Pimcore\Bundle\StudioBackendBundle\Mcp\Hydrator\McpServerHydrator;
use Pimcore\Bundle\StudioBackendBundle\Mcp\MappedParameter\McpServerParameter;
use Pimcore\Bundle\StudioBackendBundle\Mcp\Registry\McpToolRegistry;
use Pimcore\Bundle\StudioBackendBundle\Mcp\Repository\McpServerConfigRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\Mcp\Security\McpServerAccessResolver;
use Pimcore\Bundle\StudioBackendBundle\Mcp\Security\McpServerPermission;
use Pimcore\Bundle\StudioBackendBundle\Mcp\Service\McpServerConfigurationService;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Model\UserInterface;
use stdClass;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * @internal
 */
final class McpServerConfigurationServiceTest extends Unit
{
    private const ISSUER = 'https://example.test/';

    private const USER_ID = 42;

    public function testSaveConfigurationSetsOwnerDerivesScopesAndPersists(): void
    {
        $repository = $this->repository();
        $service = $this->service($repository, self::USER_ID);

        $server = $service->saveConfiguration($this->parameter('objects-read', tools: ['get_car_info']));

        $this->assertSame('objects-read', $server->getId());
        $this->assertSame(self::USER_ID, $server->getOwnerId());
        $this->assertSame(['mcp:read'], $server->getScopes());
        $this->assertSame('https://example.test/pimcore-mcp/studio/objects-read', $server->getUrl());
        // The creator is the owner, so they resolve to full write.
        $this->assertTrue($server->getCurrentUserPermissions()->isWrite());
        $this->assertTrue($repository->has('objects-read'));
        $this->assertSame(self::USER_ID, $repository->get('objects-read')->access->owner);
    }

    public function testSaveConfigurationPersistsAccessGrid(): void
    {
        $repository = $this->repository();
        $service = $this->service($repository, self::USER_ID);

        $server = $service->saveConfiguration($this->parameter(
            'shared',
            sharedUsers: [['id' => 7, 'permission' => 'write']],
            sharedRoles: [['id' => 3, 'permission' => 'read']],
        ));

        $this->assertSame(7, $server->getSharedUsers()[0]->getId());
        $this->assertSame('write', $server->getSharedUsers()[0]->getPermission());
        $this->assertEquals(
            [new McpServerAccessEntry(7, McpServerPermission::Write)],
            $repository->get('shared')->access->sharedUsers
        );
        $this->assertEquals(
            [new McpServerAccessEntry(3, McpServerPermission::Read)],
            $repository->get('shared')->access->sharedRoles
        );
    }

    public function testSaveConfigurationThrowsWhenSlugAlreadyExists(): void
    {
        $repository = $this->repository(['taken' => $this->definition('taken', new McpServerAccess(owner: 5))]);
        $service = $this->service($repository, self::USER_ID);

        $this->expectException(ElementExistsException::class);

        $service->saveConfiguration($this->parameter('taken'));
    }

    public function testGetConfigurationReturnsServerWhenReadable(): void
    {
        $repository = $this->repository(['srv' => $this->definition('srv', new McpServerAccess(owner: self::USER_ID))]);
        $service = $this->service($repository, self::USER_ID);

        $server = $service->getConfiguration('srv');

        $this->assertSame('https://example.test/pimcore-mcp/studio/srv', $server->getUrl());
    }

    public function testGetConfigurationDeniedWithoutAccess(): void
    {
        $repository = $this->repository(['srv' => $this->definition('srv', new McpServerAccess(owner: 1))]);
        $service = $this->service($repository, self::USER_ID);

        $this->expectException(ForbiddenException::class);

        $service->getConfiguration('srv');
    }

    public function testUpdateConfigurationPreservesOwnerAndLocksSlug(): void
    {
        $repository = $this->repository(['srv' => $this->definition('srv', new McpServerAccess(owner: 99))]);
        // Admin may edit any server; owner (99) must be preserved, not replaced by the actor.
        $service = $this->service($repository, self::USER_ID, isAdmin: true);

        $server = $service->updateConfiguration('srv', $this->parameter('ignored-slug', tools: ['get_car_info']));

        $this->assertSame('srv', $server->getId());
        $this->assertSame('srv', $server->getUrlSlug());
        $this->assertSame(99, $server->getOwnerId());
        $this->assertSame(['get_car_info'], $server->getTools());
        $this->assertSame(99, $repository->get('srv')->access->owner);
    }

    public function testUpdateConfigurationDeniedForReadOnlyUser(): void
    {
        $access = new McpServerAccess(owner: 1, sharedUsers: [new McpServerAccessEntry(self::USER_ID, McpServerPermission::Read)]);
        $repository = $this->repository(['srv' => $this->definition('srv', $access)]);
        $service = $this->service($repository, self::USER_ID);

        $this->expectException(ForbiddenException::class);

        $service->updateConfiguration('srv', $this->parameter('srv'));
    }

    public function testUpdateConfigurationThrowsNotFoundForUnknownId(): void
    {
        $service = $this->service($this->repository(), self::USER_ID, isAdmin: true);

        $this->expectException(NotFoundException::class);

        $service->updateConfiguration('missing', $this->parameter('missing'));
    }

    public function testListConfigurationsReturnsOnlyReadableServers(): void
    {
        $repository = $this->repository([
            'mine' => $this->definition('mine', new McpServerAccess(owner: self::USER_ID)),
            'private' => $this->definition('private', new McpServerAccess(owner: 1)),
            'global' => $this->definition('global', new McpServerAccess(owner: 1, shareGlobal: true)),
        ]);
        $service = $this->service($repository, self::USER_ID);

        $ids = array_map(static fn ($s) => $s->getId(), $service->listConfigurations());

        $this->assertSame(['mine', 'global'], $ids);
    }

    public function testListConfigurationsShowsEverythingToAdmin(): void
    {
        $repository = $this->repository([
            'a' => $this->definition('a', new McpServerAccess(owner: 1)),
            'b' => $this->definition('b', new McpServerAccess(owner: 2)),
        ]);
        $service = $this->service($repository, self::USER_ID, isAdmin: true);

        $this->assertCount(2, $service->listConfigurations());
    }

    public function testDeleteConfigurationDelegatesWhenWritable(): void
    {
        $repository = $this->repository(['gone' => $this->definition('gone', new McpServerAccess(owner: self::USER_ID))]);
        $service = $this->service($repository, self::USER_ID);

        $service->deleteConfiguration('gone');

        $this->assertFalse($repository->has('gone'));
    }

    public function testDeleteConfigurationDeniedForReadOnlyUser(): void
    {
        $access = new McpServerAccess(owner: 1, sharedUsers: [new McpServerAccessEntry(self::USER_ID, McpServerPermission::Read)]);
        $repository = $this->repository(['srv' => $this->definition('srv', $access)]);
        $service = $this->service($repository, self::USER_ID);

        $this->expectException(ForbiddenException::class);

        $service->deleteConfiguration('srv');
    }

    /**
     * @param list<int> $roles
     */
    private function service(
        McpServerConfigRepositoryInterface $repository,
        int $currentUserId,
        bool $isAdmin = false,
        array $roles = [],
    ): McpServerConfigurationService {
        return new McpServerConfigurationService(
            new McpServerHydrator(),
            $this->makeEmpty(EventDispatcherInterface::class),
            $repository,
            new McpToolRegistry([
                'get_car_info' => [
                    'class' => stdClass::class, 'method' => 'execute', 'title' => null,
                    'description' => '', 'annotations' => ['readOnlyHint' => true], 'outputSchema' => null,
                ],
                'delete_object' => [
                    'class' => stdClass::class, 'method' => 'execute', 'title' => null,
                    'description' => '', 'annotations' => ['readOnlyHint' => false], 'outputSchema' => null,
                ],
            ]),
            new McpServerAccessResolver(),
            $this->makeEmpty(SecurityServiceInterface::class, [
                'getCurrentUser' => $this->makeEmpty(UserInterface::class, [
                    'getId' => $currentUserId,
                    'isAdmin' => $isAdmin,
                    'getRoles' => $roles,
                ]),
            ]),
            self::ISSUER,
        );
    }

    /**
     * @param array<string, McpServerDefinition> $seed
     */
    private function repository(array $seed = []): McpServerConfigRepositoryInterface
    {
        return new class($seed) implements McpServerConfigRepositoryInterface {
            /** @param array<string, McpServerDefinition> $servers */
            public function __construct(private array $servers)
            {
            }

            public function list(): array
            {
                return array_values($this->servers);
            }

            public function has(string $id): bool
            {
                return isset($this->servers[$id]);
            }

            public function isWriteable(): bool
            {
                return true;
            }

            public function get(string $id): McpServerDefinition
            {
                return $this->servers[$id] ?? throw new NotFoundException('MCP server', $id);
            }

            public function save(McpServerDefinition $server): void
            {
                $this->servers[$server->id] = $server;
            }

            public function delete(string $id): void
            {
                if (!isset($this->servers[$id])) {
                    throw new NotFoundException('MCP server', $id);
                }

                unset($this->servers[$id]);
            }
        };
    }

    /**
     * @param list<string>                              $tools
     * @param list<array{id?: mixed, permission?: mixed}> $sharedUsers
     * @param list<array{id?: mixed, permission?: mixed}> $sharedRoles
     */
    private function parameter(string $slug, array $tools = [], array $sharedUsers = [], array $sharedRoles = []): McpServerParameter
    {
        return new McpServerParameter(
            name: 'Server ' . $slug,
            urlSlug: $slug,
            description: 'A server',
            tools: $tools,
            sharedUsers: $sharedUsers,
            sharedRoles: $sharedRoles,
        );
    }

    private function definition(string $id, McpServerAccess $access): McpServerDefinition
    {
        return new McpServerDefinition(
            id: $id,
            displayName: 'Server ' . $id,
            description: '',
            urlSlug: $id,
            toolIds: ['get_car_info'],
            scopes: ['mcp:read'],
            enabled: true,
            access: $access,
        );
    }
}
