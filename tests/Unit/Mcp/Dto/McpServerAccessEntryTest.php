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
use Pimcore\Bundle\StudioBackendBundle\Mcp\Dto\McpServerAccessEntry;
use Pimcore\Bundle\StudioBackendBundle\Mcp\Security\McpServerPermission;

final class McpServerAccessEntryTest extends Unit
{
    public function testFromMixedReadsGridEntry(): void
    {
        $entry = McpServerAccessEntry::fromMixed(['name' => 'john.doe', 'permission' => 'write']);

        $this->assertNotNull($entry);
        $this->assertSame('john.doe', $entry->name);
        $this->assertSame(McpServerPermission::Write, $entry->permission);
    }

    public function testFromMixedTreatsBareStringAsReadGrant(): void
    {
        $entry = McpServerAccessEntry::fromMixed('editors');

        $this->assertNotNull($entry);
        $this->assertSame('editors', $entry->name);
        $this->assertSame(McpServerPermission::Read, $entry->permission);
    }

    public function testFromMixedDefaultsUnknownOrMissingPermissionToRead(): void
    {
        $this->assertSame(McpServerPermission::Read, McpServerAccessEntry::fromMixed(['name' => 'a'])->permission);
        $this->assertSame(McpServerPermission::Read, McpServerAccessEntry::fromMixed(['name' => 'a', 'permission' => 'bogus'])->permission);
        $this->assertSame(McpServerPermission::Read, McpServerAccessEntry::fromMixed(['name' => 'a', 'permission' => 5])->permission);
    }

    public function testFromMixedReturnsNullWithoutUsableName(): void
    {
        $this->assertNull(McpServerAccessEntry::fromMixed(['permission' => 'write']));
        $this->assertNull(McpServerAccessEntry::fromMixed(['name' => '']));
        $this->assertNull(McpServerAccessEntry::fromMixed(['name' => 5]));
        $this->assertNull(McpServerAccessEntry::fromMixed(null));
    }

    public function testToArray(): void
    {
        $this->assertSame(
            ['name' => 'admins', 'permission' => 'read'],
            (new McpServerAccessEntry('admins', McpServerPermission::Read))->toArray()
        );
    }
}
