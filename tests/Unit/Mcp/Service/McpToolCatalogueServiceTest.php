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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\Mcp\Service;

use Codeception\Test\Unit;
use Mcp\Schema\ToolAnnotations;
use Pimcore\Bundle\StudioBackendBundle\Mcp\Event\PreResponse\McpToolItemEvent;
use Pimcore\Bundle\StudioBackendBundle\Mcp\Hydrator\McpToolItemHydrator;
use Pimcore\Bundle\StudioBackendBundle\Mcp\Registry\McpToolReference;
use Pimcore\Bundle\StudioBackendBundle\Mcp\Registry\McpToolRegistryInterface;
use Pimcore\Bundle\StudioBackendBundle\Mcp\Service\McpToolCatalogueService;
use stdClass;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * @internal
 */
final class McpToolCatalogueServiceTest extends Unit
{
    public function testListToolsHydratesEveryRegisteredToolAndDispatchesAnEvent(): void
    {
        $dispatched = [];
        $service = new McpToolCatalogueService(
            new McpToolItemHydrator(),
            $this->makeEmpty(McpToolRegistryInterface::class, [
                'all' => [
                    $this->tool('ping', 'Ping', readOnly: true),
                    $this->tool('delete_object', 'Delete Object', readOnly: false),
                ],
            ]),
            $this->makeEmpty(EventDispatcherInterface::class, [
                'dispatch' => function (object $event, ?string $name = null) use (&$dispatched): object {
                    $dispatched[] = $name;

                    return $event;
                },
            ]),
        );

        $items = $service->listTools();

        $this->assertCount(2, $items);
        $this->assertSame(['ping', 'delete_object'], array_map(static fn ($i) => $i->getName(), $items));
        $this->assertSame('mcp:read', $items[0]->getRequiredScope());
        $this->assertSame('mcp:write', $items[1]->getRequiredScope());
        $this->assertSame([McpToolItemEvent::EVENT_NAME, McpToolItemEvent::EVENT_NAME], $dispatched);
    }

    public function testListToolsReturnsEmptyArrayWhenNoToolsRegistered(): void
    {
        $service = new McpToolCatalogueService(
            new McpToolItemHydrator(),
            $this->makeEmpty(McpToolRegistryInterface::class, ['all' => []]),
            $this->makeEmpty(EventDispatcherInterface::class),
        );

        $this->assertSame([], $service->listTools());
    }

    private function tool(string $name, string $title, bool $readOnly): McpToolReference
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
