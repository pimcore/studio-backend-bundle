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

namespace Pimcore\Bundle\StudioBackendBundle\Mcp\Service;

use Pimcore\Bundle\StudioBackendBundle\Mcp\Event\PreResponse\McpToolItemEvent;
use Pimcore\Bundle\StudioBackendBundle\Mcp\Hydrator\McpToolItemHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\Mcp\Registry\McpToolRegistryInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * @internal
 */
final readonly class McpToolCatalogueService implements McpToolCatalogueServiceInterface
{
    public function __construct(
        private McpToolItemHydratorInterface $toolHydrator,
        private McpToolRegistryInterface $toolRegistry,
        private EventDispatcherInterface $eventDispatcher,
    ) {
    }

    public function listTools(): array
    {
        $items = [];
        foreach ($this->toolRegistry->all() as $tool) {
            $item = $this->toolHydrator->hydrate($tool);
            $this->eventDispatcher->dispatch(new McpToolItemEvent($item), McpToolItemEvent::EVENT_NAME);
            $items[] = $item;
        }

        return $items;
    }
}
