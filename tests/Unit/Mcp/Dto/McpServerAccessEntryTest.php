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

final class McpServerAccessEntryTest extends Unit
{
    public function testFromMixedReadsCamelCaseGridEntry(): void
    {
        $entry = McpServerAccessEntry::fromMixed(['name' => 'john.doe', 'canAccess' => true, 'canEdit' => false]);

        $this->assertNotNull($entry);
        $this->assertSame('john.doe', $entry->name);
        $this->assertTrue($entry->canAccess);
        $this->assertFalse($entry->canEdit);
    }

    public function testFromMixedReadsSnakeCaseFromStorage(): void
    {
        $entry = McpServerAccessEntry::fromMixed(['name' => 'a', 'can_access' => true, 'can_edit' => true]);

        $this->assertTrue($entry->canAccess);
        $this->assertTrue($entry->canEdit);
    }

    public function testFromMixedTreatsBareStringAsViewOnly(): void
    {
        $entry = McpServerAccessEntry::fromMixed('editors');

        $this->assertNotNull($entry);
        $this->assertSame('editors', $entry->name);
        $this->assertTrue($entry->canRead);
        $this->assertFalse($entry->canAccess);
        $this->assertFalse($entry->canEdit);
    }

    public function testFromMixedDefaultsCanReadTrueAndCapabilitiesFalse(): void
    {
        // A grant stored before can_read existed keeps its "listed = read" behavior.
        $entry = McpServerAccessEntry::fromMixed(['name' => 'a']);

        $this->assertTrue($entry->canRead);
        $this->assertFalse($entry->canAccess);
        $this->assertFalse($entry->canEdit);
    }

    public function testFromMixedReadsExplicitAccessWithoutRead(): void
    {
        $entry = McpServerAccessEntry::fromMixed(['name' => 'a', 'can_read' => false, 'can_access' => true]);

        $this->assertNotNull($entry);
        $this->assertFalse($entry->canRead);
        $this->assertTrue($entry->canAccess);
        $this->assertFalse($entry->canEdit);
    }

    public function testCanEditAlwaysImpliesCanRead(): void
    {
        $entry = new McpServerAccessEntry('a', canRead: false, canEdit: true);

        $this->assertTrue($entry->canRead);
        $this->assertTrue($entry->canEdit);
    }

    public function testFromMixedReturnsNullWithoutUsableName(): void
    {
        $this->assertNull(McpServerAccessEntry::fromMixed(['canAccess' => true]));
        $this->assertNull(McpServerAccessEntry::fromMixed(['name' => '']));
        $this->assertNull(McpServerAccessEntry::fromMixed(['name' => 5]));
        $this->assertNull(McpServerAccessEntry::fromMixed(null));
    }

    public function testToArrayUsesSnakeCase(): void
    {
        $this->assertSame(
            ['name' => 'admins', 'can_read' => true, 'can_access' => true, 'can_edit' => false],
            (new McpServerAccessEntry('admins', canAccess: true, canEdit: false))->toArray()
        );
    }
}
