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

namespace Pimcore\Bundle\StudioBackendBundle\User\Hydrator;

use Pimcore\Bundle\StudioBackendBundle\User\Schema\UserTreeNode;
use Pimcore\Model\User\Folder;
use Pimcore\Model\UserInterface;

/**
 * @internal
 */
final class UserTreeNodeHydrator implements UserTreeNodeHydratorInterface
{
    public function hydrate(UserInterface|Folder $user): UserTreeNode
    {
        $hasChildren = false;
        if ($user instanceof Folder) {
            $hasChildren = $user->hasChildren();
        }

        return new UserTreeNode(
            id: $user->getId(),
            name: $user->getName(),
            type: $user->getType(),
            hasChildren: $hasChildren,
        );
    }
}
