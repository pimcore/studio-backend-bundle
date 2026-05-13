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

namespace Pimcore\Bundle\StudioBackendBundle\DataObject\Schema;

use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\Model\InheritanceData;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Util\Trait\ClassDataTrait;
use Pimcore\Bundle\StudioBackendBundle\Response\ElementIcon;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\WorkflowAvailableTrait;

#[Schema(
    schema: 'DataObjectDetail',
    title: 'Data object with detail data',
    required: [
        'objectData',
        'inheritanceData',
        'draftData',
        'allowInheritance',
        'showVariants',
        'hasPreview',
        'showAppLoggerTab',
        'hasWorkflowAvailable',
    ],
    type: 'object'
)]
class DataObjectDetail extends DataObject
{
    use ClassDataTrait;
    use WorkflowAvailableTrait;

    public function __construct(
        string $key,
        string $className,
        string $type,
        bool $published,
        bool $hasChildren,
        bool $hasWorkflowWithPermissions,
        string $fullPath,
        DataObjectPermissions $permissions,
        int $index,
        string $childrenSortBy,
        string $childrenSortOrder,
        ?bool $allowVariants,
        int $id,
        int $parentId,
        string $path,
        ElementIcon $icon,
        int $userOwner,
        ?int $userModification,
        ?string $locked,
        bool $isLocked,
        ?int $creationDate,
        ?int $modificationDate,
        #[Property(description: 'Detail object data', type: 'object', example: ['fieldKey' => 'field value'])]
        private array $objectData = [],
        #[Property(
            description: 'Inheritance object data',
            type: 'object',
            example: ['fieldKey' => new InheritanceData(1, true)])
        ]
        private array $inheritanceData = [],
        #[Property(ref: DataObjectDraftData::class)]
        private ?DataObjectDraftData $draftData = null,
    ) {
        parent::__construct(
            $key,
            $className,
            $type,
            $published,
            $hasChildren,
            $hasWorkflowWithPermissions,
            $fullPath,
            $permissions,
            $index,
            $childrenSortBy,
            $childrenSortOrder,
            $allowVariants,
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

    public function getObjectData(): array
    {
        return $this->objectData;
    }

    public function setObjectData(array $objectData): void
    {
        $this->objectData = $objectData;
    }

    public function setInheritanceData(array $inheritanceData): void
    {
        $this->inheritanceData = $inheritanceData;
    }

    public function getInheritanceData(): array
    {
        return $this->inheritanceData;
    }

    public function getDraftData(): ?DataObjectDraftData
    {
        return $this->draftData;
    }

    public function setDraftData(?DataObjectDraftData $draftData): void
    {
        $this->draftData = $draftData;
    }
}
