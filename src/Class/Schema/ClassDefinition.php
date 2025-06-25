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

namespace Pimcore\Bundle\StudioBackendBundle\Class\Schema;

use OpenApi\Attributes\Items;
use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;
use Pimcore\Bundle\StudioBackendBundle\Response\ElementIcon;
use Pimcore\Bundle\StudioBackendBundle\Util\Schema\AdditionalAttributesInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\AdditionalAttributesTrait;

/**
 * @internal
 */
#[Schema(
    schema: 'ClassDefinition',
    title: 'ClassDefinition',
    required: [
        'id',
        'name',
        'title',
        'description',
        'creationDate',
        'modificationDate',
        'userOwner',
        'parentClass',
        'implementsInterfaces',
        'listingParentClass',
        'useTraits',
        'listingUseTraits',
        'encryption',
        'allowInherit',
        'allowVariants',
        'showVariants',
        'icon',
        'group',
        'showAppLoggerTab',
        'linkGeneratorReference',
        'previewGeneratorReference',
        'compositeIndices',
        'showFieldLookup',
        'propertyVisibility',
        'enableGridLocking',
        'blockedVarsForExport',
        'isWriteable',
    ],
    type: 'object'
)]
final class ClassDefinition implements AdditionalAttributesInterface
{
    use AdditionalAttributesTrait;

    public function __construct(
        #[Property(description: 'Id of class definition', type: 'string', example: 'AP')]
        private readonly string $id,
        #[Property(description: 'Name of class definition', type: 'string', example: 'AccessoryPart')]
        private readonly string $name,
        #[Property(description: 'Title', type: 'string', example: 'Accessory Part')]
        private readonly string $title,
        #[Property(description: 'Description', type: 'string', example: 'This class represents an accessory part')]
        private readonly string $description,
        #[Property(description: 'Creation date timestamp', type: 'integer', example: 1700000000)]
        private readonly ?int $creationDate,
        #[Property(description: 'Modification date timestamp', type: 'integer', example: 1700000000)]
        private readonly ?int $modificationDate,
        #[Property(description: 'User id of owner', type: 'integer', example: 1)]
        private readonly int $userOwner,
        #[Property(
            description: 'Namespace of parent class',
            type: 'string',
            example: 'App\\Model\\DataObject\\AccessoryPart'
        )]
        private readonly string $parentClass,
        #[Property(
            description: 'Interface implementations',
            type: 'string',
            example: 'App\\Model\\DataObject\\Interface'
        )]
        private readonly string $implementsInterfaces,
        #[Property(description: 'List of parent class', type: 'string', example: 'App\\Model\\DataObject\\Listing')]
        private readonly string $listingParentClass,
        #[Property(description: 'Traits usage', type: 'string', example: 'App\\Model\\DataObject\\Trait')]
        private readonly string $useTraits,
        #[Property(
            description: 'Traits usage listing',
            type: 'string',
            example: 'App\\Model\\DataObject\\ListingTrait'
        )]
        private readonly string $listingUseTraits,
        #[Property(description: 'Whether encryption is ued', type: 'boolean', example: true)]
        private readonly bool $encryption,
        #[Property(description: 'Whether inheritance is allowed', type: 'boolean', example: true)]
        private readonly bool $allowInherit,
        #[Property(description: 'Whether variants are allowed', type: 'boolean', example: true)]
        private readonly bool $allowVariants,
        #[Property(description: 'Whether variants are visible in the tree', type: 'boolean', example: true)]
        private readonly bool $showVariants,
        #[Property(description: 'icon', type: ElementIcon::class)]
        private readonly ElementIcon $icon,
        #[Property(description: 'Show application logger tab', type: 'boolean', example: true)]
        private readonly bool $showAppLoggerTab,
        #[Property(
            description: 'Namespace of link generator',
            type: 'string',
            example: 'App\\Model\\DataObject\\LinkGenerator'
        )]
        private readonly string $linkGeneratorReference,
        #[Property(
            description: 'Namespace of preview generator',
            type: 'string',
            example: 'App\\Model\\DataObject\\PreviewGenerator'
        )]
        private readonly string $previewGeneratorReference,
        #[Property(description: 'Composite indices', type: 'object', example: [])]
        private readonly array $compositeIndices,
        #[Property(description: 'Show field lookup', type: 'boolean', example: true)]
        private readonly bool $showFieldLookup,
        #[Property(description: 'Visibility of properties for grid, search, ...', type: 'object', example: [])]
        private readonly array $propertyVisibility,
        #[Property(description: 'Whether grid locking is enabled', type: 'boolean', example: true)]
        private readonly bool $enableGridLocking,
        #[Property(
            description: 'Blocked variables for export',
            type: 'array',
            items: new Items(type: 'string'),
            example: []
        )]
        /** @var string[] */
        private readonly array $blockedVarsForExport,
        #[Property(description: 'Whether the class definition can be written to', type: 'boolean', example: true)]
        private readonly bool $isWriteable,
        #[Property(description: 'Group', type: 'string', example: 'system')]
        private readonly ?string $group = null,
    ) {
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function isWriteable(): bool
    {
        return $this->isWriteable;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getCreationDate(): ?int
    {
        return $this->creationDate;
    }

    public function getModificationDate(): ?int
    {
        return $this->modificationDate;
    }

    public function getUserOwner(): int
    {
        return $this->userOwner;
    }

    public function getParentClass(): string
    {
        return $this->parentClass;
    }

    public function getImplementsInterfaces(): string
    {
        return $this->implementsInterfaces;
    }

    public function getListingParentClass(): string
    {
        return $this->listingParentClass;
    }

    public function getUseTraits(): string
    {
        return $this->useTraits;
    }

    public function getListingUseTraits(): string
    {
        return $this->listingUseTraits;
    }

    public function getEncryption(): bool
    {
        return $this->encryption;
    }

    public function getAllowInherit(): bool
    {
        return $this->allowInherit;
    }

    public function getAllowVariants(): bool
    {
        return $this->allowVariants;
    }

    public function getShowVariants(): bool
    {
        return $this->showVariants;
    }

    public function getIcon(): ElementIcon
    {
        return $this->icon;
    }

    public function getGroup(): ?string
    {
        return $this->group;
    }

    public function getShowAppLoggerTab(): bool
    {
        return $this->showAppLoggerTab;
    }

    public function getLinkGeneratorReference(): string
    {
        return $this->linkGeneratorReference;
    }

    public function getPreviewGeneratorReference(): string
    {
        return $this->previewGeneratorReference;
    }

    public function getCompositeIndices(): array
    {
        return $this->compositeIndices;
    }

    public function getShowFieldLookup(): bool
    {
        return $this->showFieldLookup;
    }

    public function getPropertyVisibility(): array
    {
        return $this->propertyVisibility;
    }

    public function getEnableGridLocking(): bool
    {
        return $this->enableGridLocking;
    }

    /**
     * @return string[]
     */
    public function getBlockedVarsForExport(): array
    {
        return $this->blockedVarsForExport;
    }

    public function getDescription(): string
    {
        return $this->description;
    }
}
