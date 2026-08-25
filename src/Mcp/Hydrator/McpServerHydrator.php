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

use Pimcore\Bundle\StudioBackendBundle\Mcp\Dto\McpServerDefinition;
use Pimcore\Bundle\StudioBackendBundle\Mcp\Schema\McpServer;
use function count;

/**
 * @internal
 */
final readonly class McpServerHydrator implements McpServerHydratorInterface
{
    public function hydrate(McpServerDefinition $definition, string $url, array $scopes, bool $writeable): McpServer
    {
        $access = $definition->access;

        return new McpServer(
            id: $definition->id,
            name: $definition->displayName,
            description: $definition->description === '' ? null : $definition->description,
            urlSlug: $definition->urlSlug,
            url: $url,
            tools: $definition->toolIds,
            scopes: $scopes,
            enabled: $definition->enabled,
            ownerId: $access->owner,
            shareGlobal: $access->shareGlobal,
            sharedUsers: $access->sharedUsers,
            sharedRoles: $access->sharedRoles,
            writeable: $writeable,
            toolCount: count($definition->toolIds),
        );
    }
}
