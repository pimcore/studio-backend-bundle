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

use Pimcore\Bundle\StaticResolverBundle\Models\User\Role\RoleResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Mcp\Dto\McpServerAccessEntry;
use Pimcore\Bundle\StudioBackendBundle\Mcp\Dto\McpServerDefinition;
use Pimcore\Model\UserInterface;
use function in_array;

/**
 * Resolves the read/write access grid on an MCP server for a user, matching by
 * user/role name (not id) so a configuration is portable across instances. The
 * precedence, modelled on the agent bundle's permission service and
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
    public function __construct(
        private readonly RoleResolverInterface $roleResolver,
    ) {
    }

    public function isAllowed(
        McpServerDefinition $server,
        McpServerPermission $permission,
        UserInterface $user
    ): bool {
        if ($user->isAdmin()) {
            return true;
        }

        $access = $server->access;
        $userName = $user->getName();

        if ($access->owner !== null && $userName !== null && $access->owner === $userName) {
            return true;
        }

        $userEntry = $this->findEntry($access->sharedUsers, $userName);
        if ($userEntry !== null) {
            return $userEntry->permission->grants($permission);
        }

        $roleNames = $this->roleNames($user);
        foreach ($access->sharedRoles as $roleEntry) {
            if (in_array($roleEntry->name, $roleNames, true) && $roleEntry->permission->grants($permission)) {
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
    private function findEntry(array $entries, ?string $name): ?McpServerAccessEntry
    {
        if ($name === null) {
            return null;
        }

        foreach ($entries as $entry) {
            if ($entry->name === $name) {
                return $entry;
            }
        }

        return null;
    }

    /**
     * The names of the roles the user holds. {@see UserInterface::getRoles()}
     * yields role ids, so each is resolved to its name for matching.
     *
     * @return list<string>
     */
    private function roleNames(UserInterface $user): array
    {
        $names = [];
        foreach ($user->getRoles() as $roleId) {
            $role = $this->roleResolver->getById($roleId);
            if ($role !== null) {
                $names[] = $role->getName();
            }
        }

        return $names;
    }
}
