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

namespace Pimcore\Bundle\StudioBackendBundle\Mcp\Hydrator;

use Pimcore\Bundle\StudioBackendBundle\Mcp\Schema\McpToolItem;
use Pimcore\Bundle\StudioBackendBundle\Mcp\Tool\McpToolInterface;

/**
 * @internal
 */
final readonly class McpToolItemHydrator implements McpToolItemHydratorInterface
{
    public function hydrate(McpToolInterface $tool): McpToolItem
    {
        $definition = $tool->getDefinition();

        return new McpToolItem(
            name: $definition->name,
            title: $definition->title,
            description: $definition->description,
            requiredScope: $definition->requiredScope(),
            readOnly: $definition->annotations->readOnly,
            destructive: $definition->annotations->destructive,
        );
    }
}
