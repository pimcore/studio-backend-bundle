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
