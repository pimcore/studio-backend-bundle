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

use Pimcore\Bundle\StudioBackendBundle\Entity\User\UserSettings;

/**
 * @internal
 */
interface UserSettingsRepositoryInterface
{
    public function update(UserSettings $userSettings): UserSettings;

    public function getByUser(int $user): ?UserSettings;
}
