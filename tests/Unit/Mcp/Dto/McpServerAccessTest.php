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

    public function testGridArrayWithCapabilities(): void
    {
        $access = McpServerAccess::fromArray([
            'owner' => 'john.doe',
            'share_global' => true,
            'shared_users' => [
                ['name' => 'alice', 'can_access' => true, 'can_edit' => true],
                ['name' => 'bob', 'can_access' => true, 'can_edit' => false],
            ],
            'shared_roles' => [['name' => 'editors', 'can_access' => false, 'can_edit' => true]],
        ]);

        $this->assertSame('john.doe', $access->owner);
        $this->assertTrue($access->shareGlobal);
        $this->assertEquals(
            [
                new McpServerAccessEntry('alice', canAccess: true, canEdit: true),
                new McpServerAccessEntry('bob', canAccess: true, canEdit: false),
            ],
            $access->sharedUsers
        );
        $this->assertEquals([new McpServerAccessEntry('editors', canAccess: false, canEdit: true)], $access->sharedRoles);
    }

    public function testReadsBareStringNamesAsViewOnly(): void
    {
        $access = McpServerAccess::fromArray(['shared_users' => ['alice', 'bob']]);

        $this->assertEquals(
            [new McpServerAccessEntry('alice'), new McpServerAccessEntry('bob')],
            $access->sharedUsers
        );
    }

    public function testDropsInvalidEntriesAndNonArrayLists(): void
    {
        $access = McpServerAccess::fromArray([
            'shared_users' => [['can_access' => true], ['name' => ''], null, ['name' => 'carol']],
            'shared_roles' => 'not-an-array',
        ]);

        $this->assertEquals([new McpServerAccessEntry('carol')], $access->sharedUsers);
        $this->assertSame([], $access->sharedRoles);
    }

    public function testRoundTrip(): void
    {
        $access = new McpServerAccess(
            owner: 'john.doe',
            shareGlobal: true,
            sharedUsers: [new McpServerAccessEntry('alice', canAccess: true, canEdit: true)],
            sharedRoles: [new McpServerAccessEntry('editors', canAccess: true, canEdit: false)],
        );

        $this->assertEquals($access, McpServerAccess::fromArray($access->toArray()));
    }

    public function testToArrayKeysAndEntryShape(): void
    {
        $array = (new McpServerAccess(sharedUsers: [new McpServerAccessEntry('alice', canAccess: true, canEdit: false)]))->toArray();

        $this->assertSame(['owner', 'share_global', 'shared_users', 'shared_roles'], array_keys($array));
        $this->assertSame([['name' => 'alice', 'can_read' => true, 'can_access' => true, 'can_edit' => false]], $array['shared_users']);
    }
}
