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

use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Model\UserInterface;

trait UserPermissionTrait
{
    private function getUserForPermissionCheck(
        SecurityServiceInterface $securityService,
        bool $checkPermission
    ): ?UserInterface {
        if (!$checkPermission) {
            return null;
        }

        return $securityService->getCurrentUser();
    }
}
