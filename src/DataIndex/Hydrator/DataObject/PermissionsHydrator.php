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

namespace Pimcore\Bundle\StudioBackendBundle\DataIndex\Hydrator\DataObject;

use Pimcore\Bundle\GenericDataIndexBundle\Permission\DataObjectPermissions as ObjectPermissions;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Schema\DataObjectPermissions;

final class PermissionsHydrator implements PermissionsHydratorInterface
{
    public function hydrate(ObjectPermissions $permissions): DataObjectPermissions
    {
        return new DataObjectPermissions(
            $permissions->isSave(),
            $permissions->isUnpublish(),
            $permissions->isLocalizedEdit(),
            $permissions->isLocalizedView(),
            $permissions->isLayouts(),
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
