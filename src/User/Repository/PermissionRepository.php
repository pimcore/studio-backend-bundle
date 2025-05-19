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

namespace Pimcore\Bundle\StudioBackendBundle\User\Repository;

use Pimcore\Model\User\Permission\Definition as PermissionsDefinition;
use Pimcore\Model\User\Permission\Definition\Listing as PermissionsListing;

/**
 * @internal
 */
final class PermissionRepository implements PermissionRepositoryInterface
{
    /**
     * @return PermissionsDefinition[]
     */
    public function getAvailablePermissions(): array
    {
        $permissions = new PermissionsListing();
        $permissions->setOrderKey('category');

        return $permissions->load();
    }
}
