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
            'owner' => 22,
            'share_global' => true,
            'shared_users' => [['id' => 1, 'permission' => 'write'], ['id' => 2, 'permission' => 'read']],
            'shared_roles' => [['id' => 7, 'permission' => 'write']],
        ]);

        $this->assertSame(22, $access->owner);
        $this->assertTrue($access->shareGlobal);
        $this->assertEquals(
            [
                new McpServerAccessEntry(1, McpServerPermission::Write),
                new McpServerAccessEntry(2, McpServerPermission::Read),
            ],
            $access->sharedUsers
        );
        $this->assertEquals([new McpServerAccessEntry(7, McpServerPermission::Write)], $access->sharedRoles);
    }

    public function testReadsLegacyFlatIdListsAsReadGrants(): void
    {
        $access = McpServerAccess::fromArray([
            'shared_users' => [3, '4'],
            'shared_roles' => [9],
        ]);

        $this->assertEquals(
            [new McpServerAccessEntry(3, McpServerPermission::Read), new McpServerAccessEntry(4, McpServerPermission::Read)],
            $access->sharedUsers
        );
        $this->assertEquals([new McpServerAccessEntry(9, McpServerPermission::Read)], $access->sharedRoles);
    }

    public function testDropsInvalidEntriesAndNonArrayLists(): void
    {
        $access = McpServerAccess::fromArray([
            'shared_users' => [['permission' => 'write'], ['id' => 'nope'], null, ['id' => 5]],
            'shared_roles' => 'not-an-array',
        ]);

        $this->assertEquals([new McpServerAccessEntry(5, McpServerPermission::Read)], $access->sharedUsers);
        $this->assertSame([], $access->sharedRoles);
    }

    public function testRoundTrip(): void
    {
        $access = new McpServerAccess(
            owner: 5,
            shareGlobal: true,
            sharedUsers: [new McpServerAccessEntry(1, McpServerPermission::Write)],
            sharedRoles: [new McpServerAccessEntry(2, McpServerPermission::Read), new McpServerAccessEntry(3, McpServerPermission::Write)],
        );

        $this->assertEquals($access, McpServerAccess::fromArray($access->toArray()));
    }

    public function testToArrayKeysAndEntryShape(): void
    {
        $array = (new McpServerAccess(sharedUsers: [new McpServerAccessEntry(1, McpServerPermission::Write)]))->toArray();

        $this->assertSame(['owner', 'share_global', 'shared_users', 'shared_roles'], array_keys($array));
        $this->assertSame([['id' => 1, 'permission' => 'write']], $array['shared_users']);
    }
}
