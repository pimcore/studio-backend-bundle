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
use Pimcore\Bundle\StudioBackendBundle\Mcp\Dto\McpServerDefinition;

final class McpServerDefinitionTest extends Unit
{
    public function testFromArrayFull(): void
    {
        $server = McpServerDefinition::fromArray('objects-read', [
            'name' => 'Data Objects (read)',
            'description' => 'Read-only data object tools',
            'url_slug' => 'data-objects-read',
            'tools' => ['get_data_object', 'search_data_objects'],
            'scopes' => ['mcp:read'],
            'enabled' => false,
            'access' => ['owner' => 22, 'share_global' => true],
        ]);

        $this->assertSame('objects-read', $server->id);
        $this->assertSame('Data Objects (read)', $server->displayName);
        $this->assertSame('Read-only data object tools', $server->description);
        $this->assertSame('data-objects-read', $server->urlSlug);
        $this->assertSame(['get_data_object', 'search_data_objects'], $server->toolIds);
        $this->assertSame(['mcp:read'], $server->scopes);
        $this->assertFalse($server->enabled);
        $this->assertSame(22, $server->access->owner);
        $this->assertTrue($server->access->shareGlobal);
    }

    public function testDefaultsFallBackToId(): void
    {
        $server = McpServerDefinition::fromArray('assets', []);

        $this->assertSame('assets', $server->id);
        $this->assertSame('assets', $server->displayName, 'name defaults to the id');
        $this->assertSame('', $server->description);
        $this->assertSame('assets', $server->urlSlug, 'url_slug defaults to the id');
        $this->assertSame([], $server->toolIds);
        $this->assertSame([], $server->scopes);
        $this->assertTrue($server->enabled, 'enabled defaults to true');
        $this->assertEquals(new McpServerAccess(), $server->access);
    }

    public function testBlankUrlSlugFallsBackToId(): void
    {
        $server = McpServerDefinition::fromArray('tags', ['url_slug' => '']);

        $this->assertSame('tags', $server->urlSlug);
    }

    public function testNonStringToolsAndScopesAreDropped(): void
    {
        $server = McpServerDefinition::fromArray('mixed', [
            'tools' => ['ok', 5, null, 'fine'],
            'scopes' => ['mcp:read', ['nested']],
        ]);

        $this->assertSame(['ok', 'fine'], $server->toolIds);
        $this->assertSame(['mcp:read'], $server->scopes);
    }

    public function testRoundTrip(): void
    {
        $server = new McpServerDefinition(
            id: 'workflows',
            displayName: 'Workflows',
            description: 'Workflow tools',
            urlSlug: 'workflows',
            toolIds: ['apply_transition'],
            scopes: ['mcp:read', 'mcp:write'],
            enabled: true,
            access: new McpServerAccess(owner: 1, sharedRoles: [9]),
        );

        $this->assertEquals($server, McpServerDefinition::fromArray($server->id, $server->toArray()));
    }

    public function testToArrayOmitsIdAndKeepsStorageKeys(): void
    {
        $array = McpServerDefinition::fromArray('x', ['name' => 'X'])->toArray();

        $this->assertArrayNotHasKey('id', $array);
        $this->assertSame(
            ['name', 'description', 'url_slug', 'tools', 'scopes', 'enabled', 'access'],
            array_keys($array)
        );
    }
}
