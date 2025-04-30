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

/**
 * @internal
 */
trait PermissionSanitationTrait
{
    // In some cases, the permissions array contains and array with empty strings as values
    // This method removes those empty strings
    private function sanitizePermissions(array $permissions): array
    {
        return array_filter($permissions, static fn ($permission) => $permission !== '');
    }
}
