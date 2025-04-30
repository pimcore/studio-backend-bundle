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
