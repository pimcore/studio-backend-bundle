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

namespace Pimcore\Bundle\StudioBackendBundle\Search\Schema;

use OpenApi\Attributes\Items;
use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;
use Pimcore\Bundle\StudioBackendBundle\Asset\Schema\Grid\ColumnSchema as AssetColumnSchema;
use Pimcore\Bundle\StudioBackendBundle\Grid\Schema\Column as DataObjectColumn;
use Pimcore\Bundle\StudioBackendBundle\Grid\Schema\Filter;
use Pimcore\Bundle\StudioBackendBundle\Util\Schema\AdditionalAttributesInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\AdditionalAttributesTrait;

/**
 * @internal
 */
#[Schema(
    schema: 'SavedSearchDetailedConfiguration',
    title: 'Saved Search Detailed Configuration',
    required: [
        'id',
        'ownerId',
        'name',
        'shareGlobal',
        'sharedUsers',
        'sharedRoles',
        'createMenuShortcut',
        'columns',
    ],
    type: 'object'
)]
final class DetailedConfiguration implements AdditionalAttributesInterface
{
    use AdditionalAttributesTrait;

    public function __construct(
        #[Property(description: 'ID of the saved search configuration', type: 'integer', example: 42)]
        private readonly int $id,
        #[Property(description: 'ID of the owner', type: 'integer', example: 42)]
        private readonly int $ownerId,
        #[Property(description: 'Name', type: 'string', example: 'My Saved Search')]
        private readonly string $name,
        #[Property(description: 'Description', type: 'string', example: 'My Saved Search Description')]
        private readonly ?string $description,
        #[Property(description: 'shareGlobal', type: 'boolean', example: false)]
        private readonly bool $shareGlobal,
        #[Property(description: 'sharedUsers', type: 'object', example: [42, 1337])]
        private readonly array $sharedUsers,
        #[Property(description: 'sharedRoles', type: 'object', example: [42, 1337])]
        private readonly array $sharedRoles,
        #[Property(description: 'createMenuShortcut', type: 'boolean', example: false)]
        private readonly bool $createMenuShortcut,
        #[Property(
            description: 'Name of the group in the menu the shortcut belongs to',
            type: 'string',
            example: 'My Group',
            nullable: true
        )]
        private readonly ?string $menuShortcutGroup,
        #[Property(description: 'Class ID for data object searches', type: 'string', example: 'car', nullable: true)]
        private readonly ?string $classId,
        #[Property(
            description: 'Element type the search targets (asset or data-object)',
            type: 'string',
            example: 'asset',
            nullable: true
        )]
        private readonly ?string $elementType,
        #[Property(description: 'Grid display columns', type: 'array', items: new Items(
            anyOf: [
                new Schema(ref: AssetColumnSchema::class),
                new Schema(ref: DataObjectColumn::class),
            ]
        ))]
        private readonly array $columns,
        #[Property(description: 'Filter data', type: 'array', items: new Items(ref: Filter::class))]
        private readonly ?array $filter,
        #[Property(description: 'Modification Date', type: 'integer', example: 1634025600)]
        private readonly ?int $modificationDate = null,
        #[Property(description: 'Creation Date', type: 'integer', example: 1634025600)]
        private readonly ?int $creationDate = null,
    ) {
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getOwnerId(): int
    {
        return $this->ownerId;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function isShareGlobal(): bool
    {
        return $this->shareGlobal;
    }

    public function getSharedUsers(): array
    {
        return $this->sharedUsers;
    }

    public function getSharedRoles(): array
    {
        return $this->sharedRoles;
    }

    public function isCreateMenuShortcut(): bool
    {
        return $this->createMenuShortcut;
    }

    public function getMenuShortcutGroup(): ?string
    {
        return $this->menuShortcutGroup;
    }

    public function getClassId(): ?string
    {
        return $this->classId;
    }

    public function getElementType(): ?string
    {
        return $this->elementType;
    }

    public function getColumns(): array
    {
        return $this->columns;
    }

    public function getFilter(): ?array
    {
        return $this->filter;
    }

    public function getModificationDate(): ?int
    {
        return $this->modificationDate;
    }

    public function getCreationDate(): ?int
    {
        return $this->creationDate;
    }
}
