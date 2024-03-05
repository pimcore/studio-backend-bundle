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

namespace Pimcore\Bundle\StudioApiBundle\Dto\Asset;

use Pimcore\Bundle\StudioApiBundle\Dto\Asset;

class Document extends Asset
{
    //use MetaData\EmbeddedMetaDataTrait;

    public function __construct(
        private readonly ?int $pageCount,
        private readonly ?string $imageThumbnailPath,
        string $iconName,
        bool $hasChildren,
        string $type,
        string $filename,
        string $mimeType,
        array $metaData,
        bool $workflowWithPermissions,
        string $fullPath,
        int $id,
        int $parentId,
        string $path,
        int $userOwner,
        int $userModification,
        ?string $locked,
        bool $isLocked,
        ?int $creationDate,
        ?int $modificationDate,
        Permissions $permissions
    ) {
        parent::__construct(
            $iconName,
            $hasChildren,
            $type,
            $filename,
            $mimeType,
            $metaData,
            $workflowWithPermissions,
            $fullPath,
            $id,
            $parentId,
            $path,
            $userOwner,
            $userModification,
            $locked,
            $isLocked,
            $creationDate,
            $modificationDate,
            $permissions
        );
    }

    public function getPageCount(): ?int
    {
        return $this->pageCount;
    }

    public function getImageThumbnailPath(): ?string
    {
        return $this->imageThumbnailPath;
    }
}
