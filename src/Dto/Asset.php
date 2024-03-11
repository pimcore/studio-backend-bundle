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

class Asset extends Element
{
    private ?string $iconName = null;

    private ?bool $hasChildren = null;

    private ?string $type = null;

    private ?string $filename = null;

    private ?string $mimeType = null;

    #[ApiProperty(genId: false)]
    private ?array $metaData = null;

    private ?bool $workflowWithPermissions = null;

    private ?string $fullPath = null;

    public function getIconName(): ?string
    {
        return $this->iconName;
    }

    public function setIconName(?string $iconName): void
    {
        $this->iconName = $iconName;
    }

    public function getHasChildren(): ?bool
    {
        return $this->hasChildren;
    }

    public function setHasChildren(?bool $hasChildren): void
    {
        $this->hasChildren = $hasChildren;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(?string $type): void
    {
        $this->type = $type;
    }

    public function getFilename(): ?string
    {
        return $this->filename;
    }

    public function setFilename(?string $filename): void
    {
        $this->filename = $filename;
    }

    public function getMimeType(): ?string
    {
        return $this->mimeType;
    }

    public function setMimeType(?string $mimeType): void
    {
        $this->mimeType = $mimeType;
    }

    public function getMetaData(): ?array
    {
        return $this->metaData;
    }

    public function getHasMetaData(): bool
    {
        return $this->metaData && count($this->metaData) > 0;
    }

    public function setMetaData(?array $metaData): void
    {
        $this->metaData = $metaData;
    }

    public function getWorkflowWithPermissions(): ?bool
    {
        return $this->workflowWithPermissions;
    }

    public function setWorkflowWithPermissions(?bool $workflowWithPermissions): void
    {
        $this->workflowWithPermissions = $workflowWithPermissions;
    }

    public function getFullPath(): ?string
    {
        return $this->fullPath;
    }

    public function setFullPath(?string $fullPath): void
    {
        $this->fullPath = $fullPath;
    }
}
