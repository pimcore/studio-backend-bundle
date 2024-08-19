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
use Pimcore\Bundle\StudioBackendBundle\Response\Element;
use Pimcore\Bundle\StudioBackendBundle\Response\ElementIcon;
use Pimcore\Bundle\StudioBackendBundle\Util\Schema\AdditionalAttributesInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\AdditionalAttributesTrait;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\CustomAttributesTrait;

#[Schema(
    title: 'Asset',
    required: [
        'iconName',
        'hasChildren',
        'type',
        'filename',
        'mimeType',
        'hasMetadata',
        'hasWorkflowWithPermissions',
        'fullPath',
        'customAttributes',
        'permissions',
    ],
    type: 'object'
)]
class Asset extends Element implements AdditionalAttributesInterface
{
    use AdditionalAttributesTrait;
    use CustomAttributesTrait;

    public function __construct(
        #[Property(description: 'Has children', type: 'bool', example: false)]
        private readonly bool $hasChildren,
        #[Property(description: 'Type', type: 'string', example: 'image')]
        private readonly string $type,
        #[Property(description: 'Filename', type: 'string', example: 'cool.jpg')]
        private readonly string $filename,
        #[Property(description: 'Mimetype', type: 'string', example: 'image/jpeg')]
        private readonly ?string $mimeType,
        #[Property(description: 'Has metadata', type: 'bool', example: false)]
        private readonly bool $hasMetadata,
        #[Property(description: 'Workflow permissions', type: 'bool', example: false)]
        private readonly bool $hasWorkflowWithPermissions,
        #[Property(description: 'Full path', type: 'string', example: '/path/to/asset.jpg')]
        private readonly string $fullPath,
        #[Property(ref: AssetPermissions::class)]
        private readonly AssetPermissions $permissions,
        int $id,
        int $parentId,
        string $path,
        ElementIcon $icon,
        int $userOwner,
        int $userModification,
        ?string $locked,
        bool $isLocked,
        ?int $creationDate,
        ?int $modificationDate
    ) {
        parent::__construct(
            $id,
            $parentId,
            $path,
            $icon,
            $userOwner,
            $userModification,
            $locked,
            $isLocked,
            $creationDate,
            $modificationDate
        );
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

    public function getHasMetadata(): bool
    {
        return $this->hasMetadata;
    }

    public function getFullPath(): string
    {
        return $this->fullPath;
    }

    public function getPermissions(): AssetPermissions
    {
        return $this->permissions;
    }
}
