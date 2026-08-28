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
use Mcp\Schema\ToolAnnotations;
use Pimcore\Bundle\StudioBackendBundle\Mcp\Hydrator\McpToolItemHydrator;
use Pimcore\Bundle\StudioBackendBundle\Mcp\Registry\McpToolReference;
use stdClass;

/**
 * @internal
 */
final class McpToolItemHydratorTest extends Unit
{
    public function testHydrateMapsReferenceAndReadOnlyScope(): void
    {
        $item = (new McpToolItemHydrator())->hydrate($this->reference('get_car_info', 'Get Car Info', readOnly: true));

        $this->assertSame('get_car_info', $item->getName());
        $this->assertSame('Get Car Info', $item->getTitle());
        $this->assertSame('Get Car Info tool', $item->getDescription());
        $this->assertSame('mcp:read', $item->getRequiredScope());
        $this->assertTrue($item->isReadOnly());
        $this->assertFalse($item->isDestructive());
    }

    public function testHydrateDerivesWriteScopeAndDestructiveHint(): void
    {
        $reference = new McpToolReference(
            name: 'delete_object',
            title: 'Delete Object',
            description: 'Deletes a data object',
            annotations: new ToolAnnotations(readOnlyHint: false, destructiveHint: true),
            outputSchema: null,
            className: stdClass::class,
            method: 'execute',
        );

        $item = (new McpToolItemHydrator())->hydrate($reference);

        $this->assertSame('mcp:write', $item->getRequiredScope());
        $this->assertFalse($item->isReadOnly());
        $this->assertTrue($item->isDestructive());
    }

    public function testHydrateFallsBackToNameWhenTitleIsNull(): void
    {
        $item = (new McpToolItemHydrator())->hydrate(new McpToolReference(
            name: 'ping',
            title: null,
            description: '',
            annotations: null,
            outputSchema: null,
            className: stdClass::class,
            method: 'execute',
        ));

        $this->assertSame('ping', $item->getTitle());
        $this->assertSame('mcp:write', $item->getRequiredScope());
    }

    private function reference(string $name, string $title, bool $readOnly): McpToolReference
    {
        return new McpToolReference(
            name: $name,
            title: $title,
            description: $title . ' tool',
            annotations: new ToolAnnotations(readOnlyHint: $readOnly),
            outputSchema: null,
            className: stdClass::class,
            method: 'execute',
        );
    }
}
