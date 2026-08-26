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
use Pimcore\Bundle\StudioBackendBundle\Mcp\Dto\McpServerAccess;
use Pimcore\Bundle\StudioBackendBundle\Mcp\Dto\McpServerAccessEntry;
use Pimcore\Bundle\StudioBackendBundle\Mcp\Dto\McpServerDefinition;
use Pimcore\Bundle\StudioBackendBundle\Mcp\Security\McpServerAccessResolver;
use Pimcore\Bundle\StudioBackendBundle\Mcp\Security\McpServerPermission;
use Pimcore\Model\UserInterface;

final class McpServerAccessResolverTest extends Unit
{
    private const int USER_ID = 42;

    private McpServerAccessResolver $resolver;

    protected function _before(): void
    {
        $this->resolver = new McpServerAccessResolver();
    }

    public function testAdminIsAlwaysAllowedBothLevels(): void
    {
        $server = $this->server(new McpServerAccess());

        $this->assertSame(['read' => true, 'write' => true], $this->resolver->resolve($server, $this->user(isAdmin: true)));
    }

    public function testOwnerHasReadAndWrite(): void
    {
        $server = $this->server(new McpServerAccess(owner: self::USER_ID));

        $this->assertSame(['read' => true, 'write' => true], $this->resolver->resolve($server, $this->user()));
    }

    public function testGlobalShareGrantsReadButNotWrite(): void
    {
        $server = $this->server(new McpServerAccess(shareGlobal: true));

        $this->assertTrue($this->resolver->isAllowed($server, McpServerPermission::Read, $this->user()));
        $this->assertFalse($this->resolver->isAllowed($server, McpServerPermission::Write, $this->user()));
    }

    public function testReadUserEntryGrantsOnlyRead(): void
    {
        $server = $this->server(new McpServerAccess(sharedUsers: [$this->entry(self::USER_ID, McpServerPermission::Read)]));

        $this->assertSame(['read' => true, 'write' => false], $this->resolver->resolve($server, $this->user()));
    }

    public function testWriteUserEntryGrantsReadAndWrite(): void
    {
        $server = $this->server(new McpServerAccess(sharedUsers: [$this->entry(self::USER_ID, McpServerPermission::Write)]));

        $this->assertSame(['read' => true, 'write' => true], $this->resolver->resolve($server, $this->user()));
    }

    public function testWriteRoleEntryGrantsReadAndWrite(): void
    {
        $server = $this->server(new McpServerAccess(sharedRoles: [$this->entry(9, McpServerPermission::Write)]));

        $this->assertSame(['read' => true, 'write' => true], $this->resolver->resolve($server, $this->user(roles: [3, 9])));
    }

    public function testDirectUserEntryWinsOverRoleAndPinsTheLevel(): void
    {
        // User is pinned to read even though a role they hold would grant write.
        $server = $this->server(new McpServerAccess(
            sharedUsers: [$this->entry(self::USER_ID, McpServerPermission::Read)],
            sharedRoles: [$this->entry(9, McpServerPermission::Write)],
        ));

        $this->assertSame(['read' => true, 'write' => false], $this->resolver->resolve($server, $this->user(roles: [9])));
    }

    public function testUnrelatedUserIsDeniedBothLevels(): void
    {
        $server = $this->server(new McpServerAccess(
            owner: 1,
            sharedUsers: [$this->entry(2, McpServerPermission::Write)],
            sharedRoles: [$this->entry(3, McpServerPermission::Write)],
        ));

        $this->assertSame(['read' => false, 'write' => false], $this->resolver->resolve($server, $this->user(roles: [4, 5])));
    }

    public function testEmptyAccessDeniesNonAdmin(): void
    {
        // Deny-by-default: nothing shared, not global, not owner → no access.
        $server = $this->server(new McpServerAccess());

        $this->assertSame(['read' => false, 'write' => false], $this->resolver->resolve($server, $this->user()));
    }

    private function entry(int $id, McpServerPermission $permission): McpServerAccessEntry
    {
        return new McpServerAccessEntry($id, $permission);
    }

    private function server(McpServerAccess $access): McpServerDefinition
    {
        return new McpServerDefinition('s', 'S', '', 's', ['ping'], ['mcp:read'], true, $access);
    }

    /**
     * @param list<int> $roles
     */
    private function user(bool $isAdmin = false, array $roles = []): UserInterface
    {
        $user = $this->createMock(UserInterface::class);
        $user->method('isAdmin')->willReturn($isAdmin);
        $user->method('getId')->willReturn(self::USER_ID);
        $user->method('getRoles')->willReturn($roles);

        return $user;
    }
}
