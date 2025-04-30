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

use Pimcore\Bundle\StudioBackendBundle\User\Schema\UserPermission;
use Pimcore\Model\User\Permission\Definition as PermissionDefinition;

/**
 * @internal
 */
final class PermissionHydrator implements PermissionHydratorInterface
{
    public function hydrate(PermissionDefinition $permission): UserPermission
    {
        return new UserPermission(
            $permission->getKey() ?? '',
            $permission->getCategory() ?? ''
        );
    }
}
