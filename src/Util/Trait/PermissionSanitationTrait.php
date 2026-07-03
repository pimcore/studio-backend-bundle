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

namespace Pimcore\Bundle\StudioBackendBundle\Util\Trait;

use function array_filter;
use function array_values;
use function in_array;

/**
 * @internal
 */
trait PermissionSanitationTrait
{
    // In some cases, the permissions array contains an array with empty strings as values.
    // This method removes those empty strings and, when $availablePermissions is provided,
    // also filters out any permission key that is not in the list of known permissions.
    // array_values() re-indexes the result so it always serializes as a JSON array, not an object.
    private function sanitizePermissions(array $permissions, array $availablePermissions = []): array
    {
        $permissions = array_filter($permissions, static fn ($permission) => $permission !== '');

        if (!empty($availablePermissions)) {
            $permissions = array_filter(
                $permissions,
                static fn ($permission) => in_array($permission, $availablePermissions, true)
            );
        }

        return array_values($permissions);
    }
}
