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

use Pimcore\Bundle\StudioBackendBundle\Entity\User\UserSettings;
use Pimcore\Bundle\StudioBackendBundle\User\Repository\UserSettingsRepositoryInterface;
use Pimcore\Model\UserInterface;

/**
 * @internal
 */
final readonly class UserSettingsService implements UserSettingsServiceInterface
{
    public function __construct(
        private UserSettingsRepositoryInterface $repository,
    ) {
    }

    public function getWelcomeScreenPath(UserInterface $user): ?string
    {
        return $this->repository->getByUser($user->getId())?->getWelcomeScreenPath();
    }

    public function updateWelcomeScreenPath(?string $welcomeScreenPath, UserInterface $user): void
    {
        $userSettings = $this->getUserSettings($user);
        $userSettings->setWelcomeScreenPath($welcomeScreenPath === '' ? null : $welcomeScreenPath);
        $this->repository->update($userSettings);
    }

    private function getUserSettings(UserInterface $user): UserSettings
    {
        $userSettings = $this->repository->getByUser($user->getId());
        if ($userSettings === null) {
            $userSettings = new UserSettings();
            $userSettings->setUser($user->getId());
        }

        return $userSettings;
    }
}
