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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\Mcp\Hydrator;

use Codeception\Test\Unit;
use Pimcore\Bundle\StudioBackendBundle\Mcp\Hydrator\McpToolItemHydrator;
use Pimcore\Bundle\StudioBackendBundle\Mcp\Tool\McpToolAnnotations;
use Pimcore\Bundle\StudioBackendBundle\Mcp\Tool\McpToolDefinition;
use Pimcore\Bundle\StudioBackendBundle\Mcp\Tool\McpToolInterface;

/**
 * @internal
 */
final class McpToolItemHydratorTest extends Unit
{
    public function testHydrateMapsDefinitionAndReadOnlyScope(): void
    {
        $tool = $this->makeEmpty(McpToolInterface::class, [
            'getDefinition' => new McpToolDefinition(
                name: 'get_car_info',
                title: 'Get Car Info',
                description: 'Returns short info about a data object',
                annotations: new McpToolAnnotations(readOnly: true),
            ),
        ]);

        $item = (new McpToolItemHydrator())->hydrate($tool);

        $this->assertSame('get_car_info', $item->getName());
        $this->assertSame('Get Car Info', $item->getTitle());
        $this->assertSame('Returns short info about a data object', $item->getDescription());
        $this->assertSame('mcp:read', $item->getRequiredScope());
        $this->assertTrue($item->isReadOnly());
        $this->assertFalse($item->isDestructive());
    }

    public function testHydrateDerivesWriteScopeForNonReadOnlyTool(): void
    {
        $tool = $this->makeEmpty(McpToolInterface::class, [
            'getDefinition' => new McpToolDefinition(
                name: 'delete_object',
                title: 'Delete Object',
                description: 'Deletes a data object',
                annotations: new McpToolAnnotations(readOnly: false, destructive: true),
            ),
        ]);

        $item = (new McpToolItemHydrator())->hydrate($tool);

        $this->assertSame('mcp:write', $item->getRequiredScope());
        $this->assertFalse($item->isReadOnly());
        $this->assertTrue($item->isDestructive());
    }
}
