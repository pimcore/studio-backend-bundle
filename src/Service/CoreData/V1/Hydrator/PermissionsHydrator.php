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

namespace Pimcore\Bundle\StudioApiBundle\Service\CoreData\V1\Hydrator;


use Exception;
use Pimcore\Bundle\StudioApiBundle\Dto\Asset\Permissions;
use Pimcore\Bundle\StudioApiBundle\Util\ValueObjects\Permission;
use Pimcore\Model\Asset;

final class PermissionsHydrator implements PermissionsHydratorInterface
{
    /**
     * @throws Exception
     */
    public function hydrate(Asset $asset): Permissions
    {
        $permission = new Permission($asset->getUserPermissions());
        return new Permissions(
            $permission->isList(),
            $permission->isView(),
            $permission->isPublish(),
            $permission->isDelete(),
            $permission->isRename(),
            $permission->isCreate(),
            $permission->isSettings(),
            $permission->isVersions(),
            $permission->isProperties()
        );
    }
}
