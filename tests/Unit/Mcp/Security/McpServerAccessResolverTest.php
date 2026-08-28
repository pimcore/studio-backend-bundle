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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\Mcp\Security;

use Codeception\Test\Unit;
use Pimcore\Bundle\StaticResolverBundle\Models\User\Role\RoleResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Mcp\Dto\McpServerAccess;
use Pimcore\Bundle\StudioBackendBundle\Mcp\Dto\McpServerAccessEntry;
use Pimcore\Bundle\StudioBackendBundle\Mcp\Dto\McpServerDefinition;
use Pimcore\Bundle\StudioBackendBundle\Mcp\Security\McpServerAccessResolver;
use Pimcore\Bundle\StudioBackendBundle\Mcp\Security\McpServerPermission;
use Pimcore\Model\User\Role;
use Pimcore\Model\UserInterface;

final class McpServerAccessResolverTest extends Unit
{
    private const string USER_NAME = 'john.doe';

    public function testAdminIsAlwaysAllowedBothLevels(): void
    {
        $server = $this->server(new McpServerAccess());

        $this->assertSame(['read' => true, 'write' => true], $this->resolver()->resolve($server, $this->user(isAdmin: true)));
    }

    public function testOwnerHasReadAndWrite(): void
    {
        $server = $this->server(new McpServerAccess(owner: self::USER_NAME));

        $this->assertSame(['read' => true, 'write' => true], $this->resolver()->resolve($server, $this->user()));
    }

    public function testGlobalShareGrantsReadButNotWrite(): void
    {
        $server = $this->server(new McpServerAccess(shareGlobal: true));
        $resolver = $this->resolver();

        $this->assertTrue($resolver->isAllowed($server, McpServerPermission::Read, $this->user()));
        $this->assertFalse($resolver->isAllowed($server, McpServerPermission::Write, $this->user()));
    }

    public function testReadUserEntryGrantsOnlyRead(): void
    {
        $server = $this->server(new McpServerAccess(sharedUsers: [$this->entry(self::USER_NAME, McpServerPermission::Read)]));

        $this->assertSame(['read' => true, 'write' => false], $this->resolver()->resolve($server, $this->user()));
    }

    public function testWriteUserEntryGrantsReadAndWrite(): void
    {
        $server = $this->server(new McpServerAccess(sharedUsers: [$this->entry(self::USER_NAME, McpServerPermission::Write)]));

        $this->assertSame(['read' => true, 'write' => true], $this->resolver()->resolve($server, $this->user()));
    }

    public function testWriteRoleEntryGrantsReadAndWrite(): void
    {
        $server = $this->server(new McpServerAccess(sharedRoles: [$this->entry('editors', McpServerPermission::Write)]));
        $resolver = $this->resolver([9 => 'editors']);

        $this->assertSame(['read' => true, 'write' => true], $resolver->resolve($server, $this->user(roles: [3, 9])));
    }

    public function testDirectUserEntryWinsOverRoleAndPinsTheLevel(): void
    {
        // Pinned to read even though a role the user holds would grant write.
        $server = $this->server(new McpServerAccess(
            sharedUsers: [$this->entry(self::USER_NAME, McpServerPermission::Read)],
            sharedRoles: [$this->entry('editors', McpServerPermission::Write)],
        ));
        $resolver = $this->resolver([9 => 'editors']);

        $this->assertSame(['read' => true, 'write' => false], $resolver->resolve($server, $this->user(roles: [9])));
    }

    public function testUnrelatedUserIsDeniedBothLevels(): void
    {
        $server = $this->server(new McpServerAccess(
            owner: 'someone.else',
            sharedUsers: [$this->entry('alice', McpServerPermission::Write)],
            sharedRoles: [$this->entry('editors', McpServerPermission::Write)],
        ));
        $resolver = $this->resolver([4 => 'viewers', 5 => 'guests']);

        $this->assertSame(['read' => false, 'write' => false], $resolver->resolve($server, $this->user(roles: [4, 5])));
    }

    public function testEmptyAccessDeniesNonAdmin(): void
    {
        $server = $this->server(new McpServerAccess());

        $this->assertSame(['read' => false, 'write' => false], $this->resolver()->resolve($server, $this->user()));
    }

    private function entry(string $name, McpServerPermission $permission): McpServerAccessEntry
    {
        return new McpServerAccessEntry($name, $permission);
    }

    private function server(McpServerAccess $access): McpServerDefinition
    {
        return new McpServerDefinition('s', 'S', '', 's', ['ping'], ['mcp:read'], true, $access);
    }

    /**
     * @param array<int, string> $roleIdToName role id → role name the resolver should return
     */
    private function resolver(array $roleIdToName = []): McpServerAccessResolver
    {
        $roleResolver = $this->createMock(RoleResolverInterface::class);
        $roleResolver->method('getById')->willReturnCallback(
            function (int $id) use ($roleIdToName): ?Role {
                if (!isset($roleIdToName[$id])) {
                    return null;
                }

                $role = $this->createMock(Role::class);
                $role->method('getName')->willReturn($roleIdToName[$id]);

                return $role;
            }
        );

        return new McpServerAccessResolver($roleResolver);
    }

    /**
     * @param list<int> $roles
     */
    private function user(bool $isAdmin = false, array $roles = []): UserInterface
    {
        $user = $this->createMock(UserInterface::class);
        $user->method('isAdmin')->willReturn($isAdmin);
        $user->method('getName')->willReturn(self::USER_NAME);
        $user->method('getRoles')->willReturn($roles);

        return $user;
    }
}
