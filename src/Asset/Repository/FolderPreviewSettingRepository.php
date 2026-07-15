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

namespace Pimcore\Bundle\StudioBackendBundle\Asset\Repository;

use Doctrine\ORM\EntityManagerInterface;
use Pimcore\Bundle\StudioBackendBundle\Entity\Asset\FolderPreviewSetting;

/**
 * @internal
 */
final readonly class FolderPreviewSettingRepository implements FolderPreviewSettingRepositoryInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function getByUserAndFolder(int $user, int $folderId): ?FolderPreviewSetting
    {
        return $this->entityManager->getRepository(FolderPreviewSetting::class)->findOneBy([
            'user' => $user,
            'assetFolderId' => $folderId,
        ]);
    }

    public function save(FolderPreviewSetting $setting): FolderPreviewSetting
    {
        $this->entityManager->persist($setting);
        $this->entityManager->flush();

        return $setting;
    }
}
