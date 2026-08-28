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
use Pimcore\Bundle\StaticResolverBundle\Models\User\Role\RoleResolverInterface;
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

    private const USER_NAME = 'john.doe';

    public function testSaveConfigurationSetsOwnerDerivesScopesAndPersists(): void
    {
        $repository = $this->repository();
        $service = $this->service($repository, self::USER_NAME);

        $server = $service->saveConfiguration($this->parameter('objects-read', tools: ['get_car_info']));

        $this->assertSame('objects-read', $server->getId());
        $this->assertSame(self::USER_NAME, $server->getOwner());
        $this->assertSame(['mcp:read'], $server->getScopes());
        $this->assertSame('https://example.test/pimcore-mcp/studio/objects-read', $server->getUrl());
        // The creator is the owner, so they resolve to full write.
        $this->assertTrue($server->getCurrentUserPermissions()->isWrite());
        $this->assertTrue($repository->has('objects-read'));
        $this->assertSame(self::USER_NAME, $repository->get('objects-read')->access->owner);
    }

    public function testSaveConfigurationPersistsAccessGrid(): void
    {
        $repository = $this->repository();
        $service = $this->service($repository, self::USER_NAME);

        $server = $service->saveConfiguration($this->parameter(
            'shared',
            sharedUsers: [['name' => 'alice', 'permission' => 'write']],
            sharedRoles: [['name' => 'editors', 'permission' => 'read']],
        ));

        $this->assertSame('alice', $server->getSharedUsers()[0]->getName());
        $this->assertSame('write', $server->getSharedUsers()[0]->getPermission());
        $this->assertEquals(
            [new McpServerAccessEntry('alice', McpServerPermission::Write)],
            $repository->get('shared')->access->sharedUsers
        );
        $this->assertEquals(
            [new McpServerAccessEntry('editors', McpServerPermission::Read)],
            $repository->get('shared')->access->sharedRoles
        );
    }

    public function testSaveConfigurationThrowsWhenSlugAlreadyExists(): void
    {
        $repository = $this->repository(['taken' => $this->definition('taken', new McpServerAccess(owner: 'someone'))]);
        $service = $this->service($repository, self::USER_NAME);

        $this->expectException(ElementExistsException::class);

        $service->saveConfiguration($this->parameter('taken'));
    }

    public function testGetConfigurationReturnsServerWhenReadable(): void
    {
        $repository = $this->repository(['srv' => $this->definition('srv', new McpServerAccess(owner: self::USER_NAME))]);
        $service = $this->service($repository, self::USER_NAME);

        $server = $service->getConfiguration('srv');

        $this->assertSame('https://example.test/pimcore-mcp/studio/srv', $server->getUrl());
    }

    public function testGetConfigurationDeniedWithoutAccess(): void
    {
        $repository = $this->repository(['srv' => $this->definition('srv', new McpServerAccess(owner: 'someone'))]);
        $service = $this->service($repository, self::USER_NAME);

        $this->expectException(ForbiddenException::class);

        $service->getConfiguration('srv');
    }

    public function testUpdateConfigurationPreservesOwnerAndLocksSlug(): void
    {
        $repository = $this->repository(['srv' => $this->definition('srv', new McpServerAccess(owner: 'original.owner'))]);
        // Admin may edit any server; the original owner must be preserved, not replaced by the actor.
        $service = $this->service($repository, self::USER_NAME, isAdmin: true);

        $server = $service->updateConfiguration('srv', $this->parameter('ignored-slug', tools: ['get_car_info']));

        $this->assertSame('srv', $server->getId());
        $this->assertSame('srv', $server->getUrlSlug());
        $this->assertSame('original.owner', $server->getOwner());
        $this->assertSame(['get_car_info'], $server->getTools());
        $this->assertSame('original.owner', $repository->get('srv')->access->owner);
    }

    public function testUpdateConfigurationDeniedForReadOnlyUser(): void
    {
        $access = new McpServerAccess(owner: 'someone', sharedUsers: [new McpServerAccessEntry(self::USER_NAME, McpServerPermission::Read)]);
        $repository = $this->repository(['srv' => $this->definition('srv', $access)]);
        $service = $this->service($repository, self::USER_NAME);

        $this->expectException(ForbiddenException::class);

        $service->updateConfiguration('srv', $this->parameter('srv'));
    }

    public function testUpdateConfigurationThrowsNotFoundForUnknownId(): void
    {
        $service = $this->service($this->repository(), self::USER_NAME, isAdmin: true);

        $this->expectException(NotFoundException::class);

        $service->updateConfiguration('missing', $this->parameter('missing'));
    }

    public function testListConfigurationsReturnsOnlyReadableServers(): void
    {
        $repository = $this->repository([
            'mine' => $this->definition('mine', new McpServerAccess(owner: self::USER_NAME)),
            'private' => $this->definition('private', new McpServerAccess(owner: 'someone')),
            'global' => $this->definition('global', new McpServerAccess(owner: 'someone', shareGlobal: true)),
        ]);
        $service = $this->service($repository, self::USER_NAME);

        $ids = array_map(static fn ($s) => $s->getId(), $service->listConfigurations());

        $this->assertSame(['mine', 'global'], $ids);
    }

    public function testListConfigurationsShowsEverythingToAdmin(): void
    {
        $repository = $this->repository([
            'a' => $this->definition('a', new McpServerAccess(owner: 'someone')),
            'b' => $this->definition('b', new McpServerAccess(owner: 'another')),
        ]);
        $service = $this->service($repository, self::USER_NAME, isAdmin: true);

        $this->assertCount(2, $service->listConfigurations());
    }

    public function testDeleteConfigurationDelegatesWhenWritable(): void
    {
        $repository = $this->repository(['gone' => $this->definition('gone', new McpServerAccess(owner: self::USER_NAME))]);
        $service = $this->service($repository, self::USER_NAME);

        $service->deleteConfiguration('gone');

        $this->assertFalse($repository->has('gone'));
    }

    public function testDeleteConfigurationDeniedForReadOnlyUser(): void
    {
        $access = new McpServerAccess(owner: 'someone', sharedUsers: [new McpServerAccessEntry(self::USER_NAME, McpServerPermission::Read)]);
        $repository = $this->repository(['srv' => $this->definition('srv', $access)]);
        $service = $this->service($repository, self::USER_NAME);

        $this->expectException(ForbiddenException::class);

        $service->deleteConfiguration('srv');
    }

    /**
     * @param list<int> $roles
     */
    private function service(
        McpServerConfigRepositoryInterface $repository,
        string $currentUserName,
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
            new McpServerAccessResolver($this->makeEmpty(RoleResolverInterface::class)),
            $this->makeEmpty(SecurityServiceInterface::class, [
                'getCurrentUser' => $this->makeEmpty(UserInterface::class, [
                    'getName' => $currentUserName,
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
     * @param list<string>                                  $tools
     * @param list<array{name?: mixed, permission?: mixed}> $sharedUsers
     * @param list<array{name?: mixed, permission?: mixed}> $sharedRoles
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
