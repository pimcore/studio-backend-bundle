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

use Doctrine\ORM\EntityManagerInterface;
use Pimcore\Bundle\StudioBackendBundle\Entity\User\UserSettings;

/**
 * @internal
 */
final readonly class UserSettingsRepository implements UserSettingsRepositoryInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
    }

    public function update(UserSettings $userSettings): UserSettings
    {
        $this->entityManager->persist($userSettings);
        $this->entityManager->flush();

        return $userSettings;
    }

    public function getByUser(int $user): ?UserSettings
    {
        return $this->entityManager->getRepository(UserSettings::class)
            ->findOneBy(['user' => $user]);
    }
}
