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
        $entry = McpServerAccessEntry::fromMixed(['id' => 42, 'permission' => 'write']);

        $this->assertNotNull($entry);
        $this->assertSame(42, $entry->id);
        $this->assertSame(McpServerPermission::Write, $entry->permission);
    }

    public function testFromMixedTreatsBareIdAsReadGrant(): void
    {
        $entry = McpServerAccessEntry::fromMixed('7');

        $this->assertNotNull($entry);
        $this->assertSame(7, $entry->id);
        $this->assertSame(McpServerPermission::Read, $entry->permission);
    }

    public function testFromMixedDefaultsUnknownOrMissingPermissionToRead(): void
    {
        $this->assertSame(McpServerPermission::Read, McpServerAccessEntry::fromMixed(['id' => 1])->permission);
        $this->assertSame(McpServerPermission::Read, McpServerAccessEntry::fromMixed(['id' => 1, 'permission' => 'bogus'])->permission);
        $this->assertSame(McpServerPermission::Read, McpServerAccessEntry::fromMixed(['id' => 1, 'permission' => 5])->permission);
    }

    public function testFromMixedReturnsNullWithoutUsableId(): void
    {
        $this->assertNull(McpServerAccessEntry::fromMixed(['permission' => 'write']));
        $this->assertNull(McpServerAccessEntry::fromMixed(['id' => 'abc']));
        $this->assertNull(McpServerAccessEntry::fromMixed(null));
    }

    public function testToArray(): void
    {
        $this->assertSame(
            ['id' => 9, 'permission' => 'read'],
            (new McpServerAccessEntry(9, McpServerPermission::Read))->toArray()
        );
    }
}
