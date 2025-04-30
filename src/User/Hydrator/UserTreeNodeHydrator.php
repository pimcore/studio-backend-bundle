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

namespace Pimcore\Bundle\StudioBackendBundle\User\Hydrator;

use Pimcore\Bundle\StudioBackendBundle\OpenApi\Schema\TreeNode;
use Pimcore\Model\User\Folder;
use Pimcore\Model\UserInterface;

/**
 * @internal
 */
final class UserTreeNodeHydrator implements UserTreeNodeHydratorInterface
{
    public function hydrate(UserInterface|Folder $user): TreeNode
    {
        $hasChildren = false;
        if ($user instanceof Folder) {
            $hasChildren = $user->hasChildren();
        }

        return new TreeNode(
            id: $user->getId(),
            name: $user->getName(),
            type: $user->getType(),
            hasChildren: $hasChildren,
        );
    }
}
