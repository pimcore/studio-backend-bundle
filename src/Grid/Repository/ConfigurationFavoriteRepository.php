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

namespace Pimcore\Bundle\StudioBackendBundle\Grid\Repository;

use Doctrine\ORM\EntityManagerInterface;
use Pimcore\Bundle\StudioBackendBundle\Entity\Grid\GridConfigurationFavorite;

/**
 * @internal
 */
final readonly class ConfigurationFavoriteRepository implements ConfigurationFavoriteRepositoryInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function getByUserAndAssetFolder(int $user, int $assetFolderId): ?GridConfigurationFavorite
    {
        return $this->entityManager->getRepository(GridConfigurationFavorite::class)
            ->findOneBy(['user' => $user, 'folder' => $assetFolderId]);
    }

    public function getByUserAndDataObject(
        int $user,
        int $dataObjectFolderId,
        string $classId
    ): ?GridConfigurationFavorite {
        return $this->entityManager->getRepository(GridConfigurationFavorite::class)
            ->findOneBy(['user' => $user, 'folder' => $dataObjectFolderId, 'classId' => $classId]);
    }
}
