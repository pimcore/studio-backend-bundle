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

namespace Pimcore\Bundle\StudioBackendBundle\DataIndex\Hydrator\DataObject;

use Pimcore\Bundle\GenericDataIndexBundle\Permission\DataObjectPermissions as ObjectPermissions;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Schema\DataObjectPermissions;

/**
 * @internal
 */
final class PermissionsHydrator implements PermissionsHydratorInterface
{
    public function hydrate(ObjectPermissions $permissions): DataObjectPermissions
    {
        return new DataObjectPermissions(
            $permissions->isSave(),
            $permissions->isUnpublish(),
            $permissions->isLocalizedEdit(),
            $permissions->isLocalizedView(),
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
