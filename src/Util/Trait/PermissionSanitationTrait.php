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
