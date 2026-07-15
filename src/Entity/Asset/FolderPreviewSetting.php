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

namespace Pimcore\Bundle\StudioBackendBundle\Entity\Asset;

use Doctrine\ORM\Mapping as ORM;

/**
 * @internal
 */
#[ORM\Entity]
#[ORM\Table(name: FolderPreviewSetting::TABLE_NAME)]
class FolderPreviewSetting
{
    public const string TABLE_NAME = 'bundle_studio_asset_folder_preview_settings';

    #[ORM\Id]
    #[ORM\Column(type: 'integer', nullable: false, options: ['unsigned' => true])]
    private int $user;

    #[ORM\Id]
    #[ORM\Column(type: 'integer', nullable: false, options: ['unsigned' => true])]
    private int $assetFolderId;

    #[ORM\Column(type: 'string', length: 10, nullable: false)]
    private string $imageSize;

    public function getUser(): int
    {
        return $this->user;
    }

    public function setUser(int $user): void
    {
        $this->user = $user;
    }

    public function getAssetFolderId(): int
    {
        return $this->assetFolderId;
    }

    public function setAssetFolderId(int $assetFolderId): void
    {
        $this->assetFolderId = $assetFolderId;
    }

    public function getImageSize(): string
    {
        return $this->imageSize;
    }

    public function setImageSize(string $imageSize): void
    {
        $this->imageSize = $imageSize;
    }
}
