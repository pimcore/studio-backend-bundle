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

namespace Pimcore\Bundle\StudioBackendBundle\Asset\Schema;

use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;
use Pimcore\Bundle\StudioBackendBundle\Asset\Schema\Type\Permissions;
use Pimcore\Bundle\StudioBackendBundle\Response\Element;
use Pimcore\Bundle\StudioBackendBundle\Util\Schema\AdditionalAttributesInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Traits\AdditionalAttributesTrait;

#[Schema(
    title: 'Asset',
    required: [
        'iconName',
        'hasChildren',
        'type',
        'filename',
        'mimeType',
        'metaData',
        'hasWorkflowWithPermissions',
        'fullPath',
    ],
    type: 'object'
)]
class Asset extends Element implements AdditionalAttributesInterface
{
    use AdditionalAttributesTrait;

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
        #[Property(description: 'Has metadata', type: 'bool', example: false)]
        private readonly bool $hasMetaData,
        #[Property(description: 'Workflow permissions', type: 'bool', example: false)]
        private readonly bool $hasWorkflowWithPermissions,
        #[Property(description: 'Full path', type: 'string', example: '/path/to/asset.jpg')]
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

    public function getHasChildren(): bool
    {
        return $this->hasChildren;
    }

    public function getHasWorkflowWithPermissions(): bool
    {
        return $this->hasWorkflowWithPermissions;
    }

    public function getFilename(): ?string
    {
        return $this->filename;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getMimeType(): ?string
    {
        return $this->mimeType;
    }

    public function getHasMetaData(): bool
    {
        return $this->hasMetaData;
    }

    public function getFullPath(): string
    {
        return $this->fullPath;
    }
}
