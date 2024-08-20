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
            ->findOneBy(['user' => $user, 'assetFolder' => $assetFolderId]);
    }
}
