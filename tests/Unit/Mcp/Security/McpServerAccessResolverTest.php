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
use Pimcore\Model\User\Role;
use Pimcore\Model\UserInterface;

final class McpServerAccessResolverTest extends Unit
{
    private const string USER_NAME = 'john.doe';

    public function testAdminHasViewAndEditButNotAccess(): void
    {
        $server = $this->server(new McpServerAccess());

        // Admins manage everything but must be granted Access explicitly.
        $this->assertSame(['view' => true, 'access' => false, 'edit' => true], $this->resolver()->resolve($server, $this->user(isAdmin: true)));
    }

    public function testAdminGetsAccessWhenExplicitlyGranted(): void
    {
        $server = $this->server(new McpServerAccess(sharedUsers: [$this->entry(self::USER_NAME, canAccess: true)]));

        $this->assertSame(['view' => true, 'access' => true, 'edit' => true], $this->resolver()->resolve($server, $this->user(isAdmin: true)));
    }

    public function testPublicGrantsViewAndAccessButNotEdit(): void
    {
        $server = $this->server(new McpServerAccess(shareGlobal: true));

        $this->assertSame(['view' => true, 'access' => true, 'edit' => false], $this->resolver()->resolve($server, $this->user()));
    }

    public function testListedWithNoCapabilitiesIsViewOnly(): void
    {
        $server = $this->server(new McpServerAccess(sharedUsers: [$this->entry(self::USER_NAME)]));

        $this->assertSame(['view' => true, 'access' => false, 'edit' => false], $this->resolver()->resolve($server, $this->user()));
    }

    public function testCanAccessGrantsAccessNotEdit(): void
    {
        $server = $this->server(new McpServerAccess(sharedUsers: [$this->entry(self::USER_NAME, canAccess: true)]));

        $this->assertSame(['view' => true, 'access' => true, 'edit' => false], $this->resolver()->resolve($server, $this->user()));
    }

    public function testCanEditGrantsEditNotAccess(): void
    {
        $server = $this->server(new McpServerAccess(sharedUsers: [$this->entry(self::USER_NAME, canEdit: true)]));

        $this->assertSame(['view' => true, 'access' => false, 'edit' => true], $this->resolver()->resolve($server, $this->user()));
    }

    public function testRoleGrantApplies(): void
    {
        $server = $this->server(new McpServerAccess(sharedRoles: [$this->entry('editors', canAccess: true, canEdit: true)]));
        $resolver = $this->resolver([9 => 'editors']);

        $this->assertSame(['view' => true, 'access' => true, 'edit' => true], $resolver->resolve($server, $this->user(roles: [3, 9])));
    }

    public function testCapabilitiesAreUnionOfUserAndRoleEntries(): void
    {
        // User entry grants only access; a role entry grants only edit — the user gets both.
        $server = $this->server(new McpServerAccess(
            sharedUsers: [$this->entry(self::USER_NAME, canAccess: true)],
            sharedRoles: [$this->entry('editors', canEdit: true)],
        ));
        $resolver = $this->resolver([9 => 'editors']);

        $this->assertSame(['view' => true, 'access' => true, 'edit' => true], $resolver->resolve($server, $this->user(roles: [9])));
    }

    public function testUnrelatedUserIsDeniedEverything(): void
    {
        $server = $this->server(new McpServerAccess(
            owner: 'someone.else',
            sharedUsers: [$this->entry('alice', canAccess: true, canEdit: true)],
            sharedRoles: [$this->entry('editors', canEdit: true)],
        ));
        $resolver = $this->resolver([4 => 'viewers', 5 => 'guests']);

        $this->assertSame(['view' => false, 'access' => false, 'edit' => false], $resolver->resolve($server, $this->user(roles: [4, 5])));
    }

    private function entry(string $name, bool $canAccess = false, bool $canEdit = false): McpServerAccessEntry
    {
        return new McpServerAccessEntry($name, $canAccess, $canEdit);
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
