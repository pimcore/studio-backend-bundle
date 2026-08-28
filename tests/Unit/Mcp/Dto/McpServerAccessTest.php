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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\Mcp\Dto;

use Codeception\Test\Unit;
use Pimcore\Bundle\StudioBackendBundle\Mcp\Dto\McpServerAccess;
use Pimcore\Bundle\StudioBackendBundle\Mcp\Dto\McpServerAccessEntry;
use Pimcore\Bundle\StudioBackendBundle\Mcp\Security\McpServerPermission;

final class McpServerAccessTest extends Unit
{
    public function testDefaultsFromEmptyArray(): void
    {
        $access = McpServerAccess::fromArray([]);

        $this->assertNull($access->owner);
        $this->assertFalse($access->shareGlobal);
        $this->assertSame([], $access->sharedUsers);
        $this->assertSame([], $access->sharedRoles);
    }

    public function testGridArrayWithLevels(): void
    {
        $access = McpServerAccess::fromArray([
            'owner' => 'john.doe',
            'share_global' => true,
            'shared_users' => [['name' => 'alice', 'permission' => 'write'], ['name' => 'bob', 'permission' => 'read']],
            'shared_roles' => [['name' => 'editors', 'permission' => 'write']],
        ]);

        $this->assertSame('john.doe', $access->owner);
        $this->assertTrue($access->shareGlobal);
        $this->assertEquals(
            [
                new McpServerAccessEntry('alice', McpServerPermission::Write),
                new McpServerAccessEntry('bob', McpServerPermission::Read),
            ],
            $access->sharedUsers
        );
        $this->assertEquals([new McpServerAccessEntry('editors', McpServerPermission::Write)], $access->sharedRoles);
    }

    public function testReadsBareStringNamesAsReadGrants(): void
    {
        $access = McpServerAccess::fromArray([
            'shared_users' => ['alice', 'bob'],
            'shared_roles' => ['editors'],
        ]);

        $this->assertEquals(
            [new McpServerAccessEntry('alice', McpServerPermission::Read), new McpServerAccessEntry('bob', McpServerPermission::Read)],
            $access->sharedUsers
        );
        $this->assertEquals([new McpServerAccessEntry('editors', McpServerPermission::Read)], $access->sharedRoles);
    }

    public function testDropsInvalidEntriesAndNonArrayLists(): void
    {
        $access = McpServerAccess::fromArray([
            'shared_users' => [['permission' => 'write'], ['name' => ''], null, ['name' => 'carol']],
            'shared_roles' => 'not-an-array',
        ]);

        $this->assertEquals([new McpServerAccessEntry('carol', McpServerPermission::Read)], $access->sharedUsers);
        $this->assertSame([], $access->sharedRoles);
    }

    public function testRoundTrip(): void
    {
        $access = new McpServerAccess(
            owner: 'john.doe',
            shareGlobal: true,
            sharedUsers: [new McpServerAccessEntry('alice', McpServerPermission::Write)],
            sharedRoles: [new McpServerAccessEntry('editors', McpServerPermission::Read), new McpServerAccessEntry('admins', McpServerPermission::Write)],
        );

        $this->assertEquals($access, McpServerAccess::fromArray($access->toArray()));
    }

    public function testToArrayKeysAndEntryShape(): void
    {
        $array = (new McpServerAccess(sharedUsers: [new McpServerAccessEntry('alice', McpServerPermission::Write)]))->toArray();

        $this->assertSame(['owner', 'share_global', 'shared_users', 'shared_roles'], array_keys($array));
        $this->assertSame([['name' => 'alice', 'permission' => 'write']], $array['shared_users']);
    }
}
