<?php
declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Bundle\StudioBackendBundle\Role\Hydrator;

use Pimcore\Bundle\StudioBackendBundle\OpenApi\Schema\TreeNode;
use Pimcore\Model\User\Role\Folder as RoleFolder;
use Pimcore\Model\User\UserRoleInterface;

/**
 * @internal
 */
final class RoleTreeNodeHydrator implements RoleTreeNodeHydratorInterface
{
    public function hydrate(UserRoleInterface|RoleFolder $role): TreeNode
    {
        $hasChildren = false;
        if ($role instanceof RoleFolder) {
            $hasChildren = $role->hasChildren();
        }

        return new TreeNode(
            id: $role->getId(),
            name: $role->getName(),
            type: $role->getType(),
            hasChildren: $hasChildren,
        );
    }
}
