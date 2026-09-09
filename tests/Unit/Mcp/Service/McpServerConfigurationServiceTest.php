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
use Pimcore\Bundle\StaticResolverBundle\Models\User\UserResolverInterface;
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
use Pimcore\Bundle\StudioBackendBundle\Mcp\Service\McpServerConfigurationService;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Model\User;
use Pimcore\Model\UserInterface;
use stdClass;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use function in_array;

/**
 * @internal
 */
final class McpServerConfigurationServiceTest extends Unit
{
    private const ISSUER = 'https://example.test/';

    private const USER_NAME = 'john.doe';

    public function testSaveConfigurationGivesOwnerImplicitReadEditButNotAccess(): void
    {
        $repository = $this->repository();
        $service = $this->service($repository, self::USER_NAME);

        $server = $service->saveConfiguration($this->parameter('objects-read', tools: ['get_car_info']));

        $this->assertSame('objects-read', $server->getId());
        $this->assertSame(self::USER_NAME, $server->getOwner());
        $this->assertSame(['mcp:read'], $server->getScopes());
        $this->assertSame('https://example.test/pimcore-mcp/studio/objects-read', $server->getUrl());
        // The owner has implicit read + edit, but access must be granted explicitly.
        $permissions = $server->getCurrentUserPermissions();
        $this->assertTrue($permissions->isCanView());
        $this->assertFalse($permissions->isCanAccess());
        $this->assertTrue($permissions->isCanEdit());
        // The owner is NOT seeded into the sharing grid.
        $this->assertSame([], $repository->get('objects-read')->access->sharedUsers);
    }

    public function testSaveConfigurationPersistsGridWithoutSeedingTheOwner(): void
    {
        $repository = $this->repository();
        $service = $this->service($repository, self::USER_NAME);

        $service->saveConfiguration($this->parameter(
            'shared',
            sharedUsers: [['name' => 'alice', 'canAccess' => true, 'canEdit' => false]],
            sharedRoles: [['name' => 'editors', 'canAccess' => false, 'canEdit' => true]],
        ));

        // Only the provided entries persist; the owner is not prepended.
        $this->assertEquals(
            [new McpServerAccessEntry('alice', canAccess: true, canEdit: false)],
            $repository->get('shared')->access->sharedUsers
        );
        $this->assertEquals(
            [new McpServerAccessEntry('editors', canAccess: false, canEdit: true)],
            $repository->get('shared')->access->sharedRoles
        );
    }

    public function testSaveConfigurationForcesReadAndEditForAnAdminSharedUser(): void
    {
        $repository = $this->repository();
        // 'admin.al' is an admin; the client submitted them as read-only + no edit.
        $service = $this->service($repository, self::USER_NAME, adminUsers: ['admin.al']);

        $service->saveConfiguration($this->parameter(
            'shared',
            sharedUsers: [['name' => 'admin.al', 'canRead' => false, 'canAccess' => true, 'canEdit' => false]],
        ));

        // Read + Edit are forced on; the submitted Access flag is preserved.
        $this->assertEquals(
            [new McpServerAccessEntry('admin.al', canRead: true, canAccess: true, canEdit: true)],
            $repository->get('shared')->access->sharedUsers
        );
    }

    public function testSaveConfigurationForcesReadAndEditWhenTheOwnerIsEnteredReadOnly(): void
    {
        $repository = $this->repository();
        // The current user (owner) is somehow submitted into the grid as read-only.
        $service = $this->service($repository, self::USER_NAME);

        $service->saveConfiguration($this->parameter(
            'shared',
            sharedUsers: [['name' => self::USER_NAME, 'canRead' => false, 'canAccess' => true, 'canEdit' => false]],
        ));

        $this->assertEquals(
            [new McpServerAccessEntry(self::USER_NAME, canRead: true, canAccess: true, canEdit: true)],
            $repository->get('shared')->access->sharedUsers
        );
    }

    public function testSaveConfigurationLeavesANonAdminNonOwnerUserUntouched(): void
    {
        $repository = $this->repository();
        $service = $this->service($repository, self::USER_NAME);

        $service->saveConfiguration($this->parameter(
            'shared',
            sharedUsers: [['name' => 'alice', 'canRead' => true, 'canAccess' => false, 'canEdit' => false]],
        ));

        // alice is neither the owner nor an admin: her read-only grant stands.
        $this->assertEquals(
            [new McpServerAccessEntry('alice', canRead: true, canAccess: false, canEdit: false)],
            $repository->get('shared')->access->sharedUsers
        );
    }

    public function testSaveConfigurationDoesNotPatchAnAdminNameInTheRoleList(): void
    {
        $repository = $this->repository();
        // A role that happens to share a name with an admin user must NOT be patched;
        // only user grants get the implicit admin/owner edit.
        $service = $this->service($repository, self::USER_NAME, adminUsers: ['admin.al']);

        $service->saveConfiguration($this->parameter(
            'shared',
            sharedRoles: [['name' => 'admin.al', 'canRead' => false, 'canAccess' => false, 'canEdit' => false]],
        ));

        $this->assertEquals(
            [new McpServerAccessEntry('admin.al', canRead: false, canAccess: false, canEdit: false)],
            $repository->get('shared')->access->sharedRoles
        );
    }

    public function testUpdateConfigurationForcesEditForAnAdminSharedUser(): void
    {
        $repository = $this->repository(['srv' => $this->definition('srv', new McpServerAccess(owner: 'original.owner'))]);
        $service = $this->service($repository, self::USER_NAME, isAdmin: true, adminUsers: ['admin.al']);

        $service->updateConfiguration('srv', $this->parameter(
            'srv',
            sharedUsers: [['name' => 'admin.al', 'canRead' => false, 'canAccess' => false, 'canEdit' => false]],
        ));

        $this->assertEquals(
            [new McpServerAccessEntry('admin.al', canRead: true, canAccess: false, canEdit: true)],
            $repository->get('srv')->access->sharedUsers
        );
    }

    public function testSaveConfigurationThrowsWhenSlugAlreadyExists(): void
    {
        $repository = $this->repository(['taken' => $this->definition('taken', new McpServerAccess(owner: 'someone'))]);
        $service = $this->service($repository, self::USER_NAME);

        $this->expectException(ElementExistsException::class);

        $service->saveConfiguration($this->parameter('taken'));
    }

    public function testGetConfigurationAllowedForListedViewer(): void
    {
        $access = new McpServerAccess(owner: 'someone', sharedUsers: [new McpServerAccessEntry(self::USER_NAME)]);
        $repository = $this->repository(['srv' => $this->definition('srv', $access)]);
        $service = $this->service($repository, self::USER_NAME);

        $this->assertSame('https://example.test/pimcore-mcp/studio/srv', $service->getConfiguration('srv')->getUrl());
    }

    public function testGetConfigurationAllowedForAdmin(): void
    {
        $repository = $this->repository(['srv' => $this->definition('srv', new McpServerAccess(owner: 'someone'))]);
        $service = $this->service($repository, self::USER_NAME, isAdmin: true);

        $this->assertSame('srv', $service->getConfiguration('srv')->getId());
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
        // Admin has Edit on all servers; the original owner must be preserved.
        $service = $this->service($repository, self::USER_NAME, isAdmin: true);

        $server = $service->updateConfiguration('srv', $this->parameter('ignored-slug', tools: ['get_car_info']));

        $this->assertSame('srv', $server->getId());
        $this->assertSame('srv', $server->getUrlSlug());
        $this->assertSame('original.owner', $server->getOwner());
        $this->assertSame(['get_car_info'], $server->getTools());
        $this->assertSame('original.owner', $repository->get('srv')->access->owner);
    }

    public function testUpdateConfigurationDeniedForViewOnlyUser(): void
    {
        $access = new McpServerAccess(owner: 'someone', sharedUsers: [new McpServerAccessEntry(self::USER_NAME)]);
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

    public function testListConfigurationsReturnsOnlyViewableServers(): void
    {
        $repository = $this->repository([
            'mine' => $this->definition('mine', new McpServerAccess(owner: 'x', sharedUsers: [new McpServerAccessEntry(self::USER_NAME)])),
            'private' => $this->definition('private', new McpServerAccess(owner: 'x')),
            'public' => $this->definition('public', new McpServerAccess(owner: 'x', shareGlobal: true)),
        ]);
        $service = $this->service($repository, self::USER_NAME);

        $ids = array_map(static fn ($s) => $s->getId(), $service->listConfigurations());

        $this->assertSame(['mine', 'public'], $ids);
    }

    public function testListConfigurationsShowsEverythingToAdmin(): void
    {
        $repository = $this->repository([
            'a' => $this->definition('a', new McpServerAccess(owner: 'x')),
            'b' => $this->definition('b', new McpServerAccess(owner: 'y')),
        ]);
        $service = $this->service($repository, self::USER_NAME, isAdmin: true);

        $this->assertCount(2, $service->listConfigurations());
    }

    public function testDeleteConfigurationAllowedForEditor(): void
    {
        $access = new McpServerAccess(owner: 'x', sharedUsers: [new McpServerAccessEntry(self::USER_NAME, canEdit: true)]);
        $repository = $this->repository(['gone' => $this->definition('gone', $access)]);
        $service = $this->service($repository, self::USER_NAME);

        $service->deleteConfiguration('gone');

        $this->assertFalse($repository->has('gone'));
    }

    public function testDeleteConfigurationDeniedForViewOnlyUser(): void
    {
        $access = new McpServerAccess(owner: 'x', sharedUsers: [new McpServerAccessEntry(self::USER_NAME)]);
        $repository = $this->repository(['srv' => $this->definition('srv', $access)]);
        $service = $this->service($repository, self::USER_NAME);

        $this->expectException(ForbiddenException::class);

        $service->deleteConfiguration('srv');
    }

    /**
     * @param list<int>    $roles
     * @param list<string> $adminUsers names the {@see UserResolverInterface} reports as admins
     */
    private function service(
        McpServerConfigRepositoryInterface $repository,
        string $currentUserName,
        bool $isAdmin = false,
        array $roles = [],
        array $adminUsers = [],
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
            $this->userResolver($adminUsers),
            self::ISSUER,
        );
    }

    /**
     * A resolver that reports the given names as admin users and everyone else
     * (including unknown names) as non-admin.
     *
     * @param list<string> $adminUsers
     */
    private function userResolver(array $adminUsers): UserResolverInterface
    {
        return $this->makeEmpty(UserResolverInterface::class, [
            'getByName' => static fn (string $name): ?User => in_array($name, $adminUsers, true)
                ? (new User())->setAdmin(true)
                : null,
        ]);
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
     * @param list<string>                                                    $tools
     * @param list<array{name?: mixed, canAccess?: mixed, canEdit?: mixed}>   $sharedUsers
     * @param list<array{name?: mixed, canAccess?: mixed, canEdit?: mixed}>   $sharedRoles
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
