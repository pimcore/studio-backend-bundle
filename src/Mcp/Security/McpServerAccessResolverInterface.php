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

use Pimcore\Bundle\StudioBackendBundle\Mcp\Dto\McpServerDefinition;
use Pimcore\Model\UserInterface;

/**
 * Decides whether a user may access a given MCP server at a requested level
 * ({@see McpServerPermission}). Deny-by-default: an admin or the owner always
 * passes, otherwise the user must be granted the level directly, via a role, or
 * (for read only) by the global-share flag. Used both by the Studio API and by
 * the runtime serving endpoint (which asks for {@see McpServerPermission::Read}).
 *
 * @internal
 */
interface McpServerAccessResolverInterface
{
    public function isAllowed(
        McpServerDefinition $server,
        McpServerPermission $permission,
        UserInterface $user
    ): bool;

    /**
     * Both levels resolved at once, for building the response DTO.
     *
     * @return array{read: bool, write: bool}
     */
    public function resolve(McpServerDefinition $server, UserInterface $user): array;
}
