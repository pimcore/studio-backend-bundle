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

namespace Pimcore\Bundle\StudioApiBundle\Service\GenericData\V1\Hydrator;

use Pimcore\Bundle\GenericDataIndexBundle\Permission\AssetPermissions;
use Pimcore\Bundle\StudioApiBundle\Response\Asset\Permissions;

final class PermissionsHydrator implements PermissionsHydratorInterface
{
    public function hydrate(AssetPermissions $permissions): Permissions
    {
        return new Permissions(
            $permissions->isList(),
            $permissions->isView(),
            $permissions->isPublish(),
            $permissions->isDelete(),
            $permissions->isRename(),
            $permissions->isCreate(),
            $permissions->isSettings(),
            $permissions->isVersions(),
            $permissions->isProperties()
        );
    }
}
