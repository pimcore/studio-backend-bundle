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

namespace Pimcore\Bundle\StudioBackendBundle\Security;

use InvalidArgumentException;

final readonly class PermissionsToCheck
{
    public function __construct(
        private array $permissionsToCheck
    ) {
        if (empty($this->permissionsToCheck)) {
            throw new InvalidArgumentException('Permissions to check must not be empty');
        }
    }

    public function getPermissionsToCheck(): array
    {
        return $this->permissionsToCheck;
    }
}
