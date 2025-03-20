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

namespace Pimcore\Bundle\StudioBackendBundle\User\Repository;

use Doctrine\ORM\EntityManagerInterface;
use Pimcore\Bundle\StudioBackendBundle\Entity\Perspective\UserPerspectiveData;

/**
 * @internal
 */
final readonly class PerspectiveRepository implements PerspectiveRepositoryInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
    }

    public function update(UserPerspectiveData $userPerspectives): UserPerspectiveData
    {
        $this->entityManager->persist($userPerspectives);
        $this->entityManager->flush();

        return $userPerspectives;
    }

    public function getByUser(int $user): ?UserPerspectiveData
    {
        return $this->entityManager->getRepository(UserPerspectiveData::class)
            ->findOneBy(['user' => $user]);
    }

    public function getUserActivePerspective(int $user): ?string {

        return $this->getByUser($user)?->getActivePerspective();
    }

    public function listUserPerspectives(int $user): array {
        $perspectives = $this->getByUser($user)?->getPerspectives();

        return $perspectives ? explode(',', $perspectives) : [];
    }
}
