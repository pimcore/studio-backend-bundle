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

use Pimcore\Bundle\StudioBackendBundle\Mcp\McpScopes;
use Pimcore\Bundle\StudioBackendBundle\Mcp\Registry\McpToolReference;
use Pimcore\Bundle\StudioBackendBundle\Mcp\Schema\McpToolItem;

/**
 * @internal
 */
final readonly class McpToolItemHydrator implements McpToolItemHydratorInterface
{
    public function hydrate(McpToolReference $tool): McpToolItem
    {
        return new McpToolItem(
            name: $tool->name,
            title: $tool->title ?? $tool->name,
            description: $tool->description,
            requiredScope: McpScopes::forReadOnly($tool->isReadOnly()),
            readOnly: $tool->isReadOnly(),
            destructive: $tool->isDestructive(),
        );
    }
}
