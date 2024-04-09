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

use OpenApi\Attributes\Items;
use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;
use Pimcore\Bundle\StudioApiBundle\Dto\Asset\Permissions;

#[Schema(
    title: 'Asset',
    type: 'object'
)]
class Asset extends Element
{
    public function __construct(
        #[Property(description: 'IconName', type: 'string', example: 'pimcore_icon_pdf')]
        private readonly string $iconName,
        #[Property(description: 'Has children', type: 'bool', example: false)]
        private readonly bool $hasChildren,
        #[Property(description: 'Type', type: 'string', example: 'image')]
        private readonly string $type,
        #[Property(description: 'Filename', type: 'string', example: 'cool.jpg')]
        private readonly string $filename,
        #[Property(description: 'Mimetype', type: 'string', example: 'image/jpeg')]
        private readonly ?string $mimeType,
        #[Property(
            description: 'Metadata',
            type: 'array',
            items: new Items(type: 'string', example: 'meta_data_example'),
            example: 'pimcore_icon_pdf'
        )]
        private readonly array $metaData,
        #[Property(description: 'Workflow permissions', type: 'bool', example: false)]
        private readonly bool $workflowWithPermissions,
        #[Property(description: 'Full path', type: 'string', example: '/path/to/asset.jpg')]
        private readonly string $fullPath,
        #[Property(description: 'ID', type: 'integer', example: 83)]
        int $id,
        #[Property(description: 'Parent ID', type: 'integer', example: 1)]
        int $parentId,
        #[Property(description: 'path', type: 'string', example: 'path/to/asset.jpg')]
        string $path,
        #[Property(description: 'owner', type: 'integer', example: 1)]
        int $userOwner,
        #[Property(description: 'User modification', type: 'integer', example: 1)]
        int $userModification,
        #[Property(description: 'Locked', type: 'string', example: 'locked')]
        ?string $locked,
        #[Property(description: 'Is locked', type: 'bool', example: false)]
        bool $isLocked,
        #[Property(description: 'Creation date', type: 'integer', example: 1634025600)]
        ?int $creationDate,
        #[Property(description: 'Modification date', type: 'integer', example: 1634025800)]
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

    public function getFilename(): ?string
    {
        return $this->filename;
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
