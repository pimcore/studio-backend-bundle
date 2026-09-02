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
 * Resolves a user's capabilities on an MCP server, matching users and roles by
 * name. Grants are the union of the user's direct entry and any of their role
 * entries (most-permissive wins). The rules:
 *
 *  - View   — admin, the owner, a public server ({@see McpServerAccess::$shareGlobal}),
 *             OR the user is listed at all (as a user or via a role).
 *  - Access — the server is public, OR a matching entry grants Access. Neither admins
 *             nor the owner get Access implicitly; it must be granted explicitly (they
 *             can add themselves to the user list with Access).
 *  - Edit   — admin, the owner, OR a matching entry grants Edit. Public does not grant Edit.
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
        McpServerCapability $capability,
        UserInterface $user
    ): bool {
        $resolved = $this->resolve($server, $user);

        return match ($capability) {
            McpServerCapability::View => $resolved['view'],
            McpServerCapability::Access => $resolved['access'],
            McpServerCapability::Edit => $resolved['edit'],
        };
    }

    public function resolve(McpServerDefinition $server, UserInterface $user): array
    {
        $access = $server->access;
        $isAdmin = $user->isAdmin();
        $isOwner = $access->owner !== null && $access->owner !== '' && $user->getName() === $access->owner;

        $entries = $this->matchingEntries($access->sharedUsers, $access->sharedRoles, $user);
        $listed = $entries !== [];

        $entryAccess = false;
        $entryEdit = false;
        foreach ($entries as $entry) {
            $entryAccess = $entryAccess || $entry->canAccess;
            $entryEdit = $entryEdit || $entry->canEdit;
        }

        return [
            'view' => $isAdmin || $isOwner || $access->shareGlobal || $listed,
            'access' => $access->shareGlobal || $entryAccess,
            'edit' => $isAdmin || $isOwner || $entryEdit,
        ];
    }

    /**
     * The user's own entry plus any of their role entries.
     *
     * @param list<McpServerAccessEntry> $userEntries
     * @param list<McpServerAccessEntry> $roleEntries
     *
     * @return list<McpServerAccessEntry>
     */
    private function matchingEntries(array $userEntries, array $roleEntries, UserInterface $user): array
    {
        $matched = [];

        $userName = $user->getName();
        if ($userName !== null) {
            foreach ($userEntries as $entry) {
                if ($entry->name === $userName) {
                    $matched[] = $entry;
                }
            }
        }

        $roleNames = $this->roleNames($user);
        foreach ($roleEntries as $entry) {
            if (in_array($entry->name, $roleNames, true)) {
                $matched[] = $entry;
            }
        }

        return $matched;
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
