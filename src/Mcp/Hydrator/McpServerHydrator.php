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

use Pimcore\Bundle\StudioBackendBundle\Mcp\Dto\McpServerAccessEntry;
use Pimcore\Bundle\StudioBackendBundle\Mcp\Dto\McpServerDefinition;
use Pimcore\Bundle\StudioBackendBundle\Mcp\Schema\McpServer;
use Pimcore\Bundle\StudioBackendBundle\Mcp\Schema\McpServerAccessGrant;
use Pimcore\Bundle\StudioBackendBundle\Mcp\Schema\McpServerUserPermissions;
use function array_map;
use function count;

/**
 * @internal
 */
final readonly class McpServerHydrator implements McpServerHydratorInterface
{
    public function hydrate(
        McpServerDefinition $definition,
        string $url,
        array $scopes,
        bool $writeable,
        McpServerUserPermissions $currentUserPermissions
    ): McpServer {
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
            owner: $access->owner,
            shareGlobal: $access->shareGlobal,
            sharedUsers: $this->grants($access->sharedUsers),
            sharedRoles: $this->grants($access->sharedRoles),
            writeable: $writeable,
            currentUserPermissions: $currentUserPermissions,
            toolCount: count($definition->toolIds),
        );
    }

    /**
     * @param list<McpServerAccessEntry> $entries
     *
     * @return list<McpServerAccessGrant>
     */
    private function grants(array $entries): array
    {
        return array_map(
            static fn (McpServerAccessEntry $entry): McpServerAccessGrant => new McpServerAccessGrant(
                $entry->name,
                $entry->canAccess,
                $entry->canEdit,
            ),
            $entries
        );
    }
}
