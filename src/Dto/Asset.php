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

namespace Pimcore\Bundle\StudioApiBundle\Dto;

use ApiPlatform\Metadata\ApiProperty;
use Pimcore\Bundle\StudioApiBundle\Dto\Asset\Permissions;

class Asset extends Element
{
    public function __construct(
        private readonly string $iconName,
        private readonly bool $hasChildren,
        private readonly string $type,
        private string $filename,
        private readonly ?string $mimeType,
        private readonly array $metaData,
        private readonly bool $workflowWithPermissions,
        private readonly string $fullPath,
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

    public function getIconName(): string
    {
        return $this->iconName;
    }

    public function hasChildren(): bool
    {
        return $this->hasChildren;
    }

    public function hasWorkflowWithPermissions(): bool
    {
        return $this->workflowWithPermissions;
    }

    public function getFilename(): string
    {
        return $this->filename;
    }

    public function setFilename(string $filename): void
    {
        $this->filename = $filename;
    }

    public function getType(): string
    {
        return $this->type;
    }

    //    public function getCustomSettings(): array
    //    {
    //        return $this->asset->getCustomSettings();
    //    }

    public function getMimeType(): ?string
    {
        return $this->mimeType;
    }

    #[ApiProperty(genId: false)]
    public function getMetadata(): array
    {
        return $this->metaData;
    }

    public function getHasMetaData(): bool
    {
        return count($this->metaData) > 0;
    }

    public function getFullPath(): string
    {
        return $this->fullPath;
    }

    //    /**
    //     * @param User|null $user
    //     *
    //     * @return array
    //     *
    //     * @throws Exception
    //     */
    //    public function getUserPermissions(?User $user = null): array
    //    {
    //        return $this->permis;
    //    }
}
