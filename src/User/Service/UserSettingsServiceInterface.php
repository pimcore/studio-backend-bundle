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

namespace Pimcore\Bundle\StudioBackendBundle\User\Service;

use Pimcore\Model\UserInterface;

/**
 * @internal
 */
interface UserSettingsServiceInterface
{
    public function getWelcomeScreenDashboardId(UserInterface $user): ?string;

    /**
     * Null or an empty string clears the stored path.
     */
    public function updateWelcomeScreenDashboardId(?string $welcomeScreenDashboardId, UserInterface $user): void;
}
