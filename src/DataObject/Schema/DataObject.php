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

namespace Pimcore\Bundle\StudioBackendBundle\DataObject\Schema;

use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;
use Pimcore\Bundle\StudioBackendBundle\Response\Element;
use Pimcore\Bundle\StudioBackendBundle\Response\ElementIcon;
use Pimcore\Bundle\StudioBackendBundle\Util\Schema\AdditionalAttributesInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\AdditionalAttributesTrait;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\CustomAttributesTrait;

#[Schema(
    title: 'DataObject',
    required: [
        'key',
        'className',
        'type',
        'published',
        'hasChildren',
        'hasWorkflowWithPermissions',
        'fullPath',
        'customAttributes',
        'permissions',
        'index',
    ],
    type: 'object'
)]
class DataObject extends Element implements AdditionalAttributesInterface
{
    use AdditionalAttributesTrait;
    use CustomAttributesTrait;

    public function __construct(
        #[Property(description: 'Key', type: 'string', example: 'Giulietta')]
        private readonly string $key,
        #[Property(description: 'Class name', type: 'string', example: 'car')]
        private readonly string $className,
        #[Property(description: 'Type', type: 'string', example: 'image')]
        private readonly string $type,
        #[Property(description: 'Published', type: 'bool', example: false)]
        private readonly bool $published,
        #[Property(description: 'Has children', type: 'bool', example: false)]
        private readonly bool $hasChildren,
        #[Property(description: 'Workflow permissions', type: 'bool', example: false)]
        private readonly bool $hasWorkflowWithPermissions,
        #[Property(description: 'Full path', type: 'string', example: '/path/to/dataObject')]
        private readonly string $fullPath,
        #[Property(ref: DataObjectPermissions::class)]
        private readonly DataObjectPermissions $permissions,
        #[Property(description: 'Custom index', type: 'integer', example: 0)]
        private readonly int $index,
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

    public function getKey(): string
    {
        return $this->key;
    }

    public function getClassName(): string
    {
        return $this->className;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function isPublished(): bool
    {
        return $this->published;
    }

    public function getHasChildren(): bool
    {
        return $this->hasChildren;
    }

    public function getHasWorkflowWithPermissions(): bool
    {
        return $this->hasWorkflowWithPermissions;
    }

    public function getFullPath(): string
    {
        return $this->fullPath;
    }

    public function getPermissions(): DataObjectPermissions
    {
        return $this->permissions;
    }

    public function getIndex(): int
    {
        return $this->index;
    }

    public function getFilename()
    {
        return $this->key;
    }
}
