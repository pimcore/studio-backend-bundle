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

    public function testFullArray(): void
    {
        $access = McpServerAccess::fromArray([
            'owner' => 22,
            'share_global' => true,
            'shared_users' => [1, 2],
            'shared_roles' => [7],
        ]);

        $this->assertSame(22, $access->owner);
        $this->assertTrue($access->shareGlobal);
        $this->assertSame([1, 2], $access->sharedUsers);
        $this->assertSame([7], $access->sharedRoles);
    }

    public function testCoercesNumericIdsAndDropsNonNumeric(): void
    {
        $access = McpServerAccess::fromArray([
            'owner' => '42',
            'shared_users' => ['3', 4, 'nope', null],
            'shared_roles' => 'not-an-array',
        ]);

        $this->assertSame(42, $access->owner);
        $this->assertSame([3, 4], $access->sharedUsers);
        $this->assertSame([], $access->sharedRoles);
    }

    public function testRoundTrip(): void
    {
        $access = new McpServerAccess(owner: 5, shareGlobal: true, sharedUsers: [1], sharedRoles: [2, 3]);

        $this->assertEquals($access, McpServerAccess::fromArray($access->toArray()));
    }

    public function testToArrayKeys(): void
    {
        $array = (new McpServerAccess())->toArray();

        $this->assertSame(['owner', 'share_global', 'shared_users', 'shared_roles'], array_keys($array));
    }
}
