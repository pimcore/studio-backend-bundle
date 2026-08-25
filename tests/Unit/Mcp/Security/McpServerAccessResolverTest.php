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
use Pimcore\Bundle\StudioBackendBundle\Mcp\Dto\McpServerDefinition;
use Pimcore\Bundle\StudioBackendBundle\Mcp\Security\McpServerAccessResolver;
use Pimcore\Model\UserInterface;

final class McpServerAccessResolverTest extends Unit
{
    private const int USER_ID = 42;

    private McpServerAccessResolver $resolver;

    protected function _before(): void
    {
        $this->resolver = new McpServerAccessResolver();
    }

    public function testAdminIsAlwaysAllowed(): void
    {
        // No sharing at all, but admin bypasses everything.
        $this->assertTrue($this->resolver->isAllowed($this->server(new McpServerAccess()), $this->user(isAdmin: true)));
    }

    public function testGlobalShareAllowsAnyUser(): void
    {
        $server = $this->server(new McpServerAccess(shareGlobal: true));

        $this->assertTrue($this->resolver->isAllowed($server, $this->user()));
    }

    public function testOwnerIsAllowed(): void
    {
        $server = $this->server(new McpServerAccess(owner: self::USER_ID));

        $this->assertTrue($this->resolver->isAllowed($server, $this->user()));
    }

    public function testSharedUserIsAllowed(): void
    {
        $server = $this->server(new McpServerAccess(sharedUsers: [7, self::USER_ID]));

        $this->assertTrue($this->resolver->isAllowed($server, $this->user()));
    }

    public function testSharedRoleIsAllowed(): void
    {
        $server = $this->server(new McpServerAccess(sharedRoles: [9]));

        $this->assertTrue($this->resolver->isAllowed($server, $this->user(roles: [3, 9])));
    }

    public function testUnrelatedUserIsDenied(): void
    {
        $server = $this->server(new McpServerAccess(owner: 1, sharedUsers: [2], sharedRoles: [3]));

        $this->assertFalse($this->resolver->isAllowed($server, $this->user(roles: [4, 5])));
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
