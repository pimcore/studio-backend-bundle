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

namespace Pimcore\Bundle\StudioBackendBundle\Mcp\Security;

use Pimcore\Bundle\StudioBackendBundle\Mcp\Dto\McpServerAccessEntry;
use Pimcore\Bundle\StudioBackendBundle\Mcp\Dto\McpServerDefinition;
use Pimcore\Model\UserInterface;
use function in_array;

/**
 * Resolves the read/write access grid on an MCP server for a user. The precedence,
 * modelled on the agent bundle's permission service but keyed by id and
 * deny-by-default, is:
 *
 *  1. admin — always allowed (both levels)
 *  2. owner — always allowed (implicit write, not downgradable via the grid)
 *  3. a direct user entry is authoritative — it decides outright, blocking role
 *     fallback even when it denies the requested level
 *  4. otherwise any of the user's roles that grants the level
 *  5. read only: the global-share flag
 *  6. otherwise denied
 *
 * @internal
 */
final class McpServerAccessResolver implements McpServerAccessResolverInterface
{
    public function isAllowed(
        McpServerDefinition $server,
        McpServerPermission $permission,
        UserInterface $user
    ): bool {
        if ($user->isAdmin()) {
            return true;
        }

        $access = $server->access;

        if ($access->owner !== null && $access->owner === $user->getId()) {
            return true;
        }

        $userEntry = $this->findEntry($access->sharedUsers, $user->getId());
        if ($userEntry !== null) {
            return $userEntry->permission->grants($permission);
        }

        foreach ($access->sharedRoles as $roleEntry) {
            if (in_array($roleEntry->id, $user->getRoles(), true) && $roleEntry->permission->grants($permission)) {
                return true;
            }
        }

        return $permission === McpServerPermission::Read && $access->shareGlobal;
    }

    public function resolve(McpServerDefinition $server, UserInterface $user): array
    {
        return [
            'read' => $this->isAllowed($server, McpServerPermission::Read, $user),
            'write' => $this->isAllowed($server, McpServerPermission::Write, $user),
        ];
    }

    /**
     * @param list<McpServerAccessEntry> $entries
     */
    private function findEntry(array $entries, ?int $id): ?McpServerAccessEntry
    {
        if ($id === null) {
            return null;
        }

        foreach ($entries as $entry) {
            if ($entry->id === $id) {
                return $entry;
            }
        }

        return null;
    }
}
