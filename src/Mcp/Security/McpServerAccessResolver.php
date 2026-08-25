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
use function array_intersect;
use function in_array;

/**
 * The same union resolution the bundle's config sharing uses
 * ({@see \Pimcore\Bundle\StudioBackendBundle\Configuration\Share\Service\ConfigurationShareService}):
 * an admin always passes; otherwise the server must be global, owned by the user,
 * or shared with the user directly or via one of their roles.
 *
 * @internal
 */
final class McpServerAccessResolver implements McpServerAccessResolverInterface
{
    public function isAllowed(McpServerDefinition $server, UserInterface $user): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        $access = $server->access;

        if ($access->shareGlobal) {
            return true;
        }

        if ($access->owner !== null && $access->owner === $user->getId()) {
            return true;
        }

        if (in_array($user->getId(), $access->sharedUsers, true)) {
            return true;
        }

        return array_intersect($access->sharedRoles, $user->getRoles()) !== [];
    }
}
