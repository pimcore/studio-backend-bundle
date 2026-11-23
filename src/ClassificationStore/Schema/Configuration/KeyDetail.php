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

namespace Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Schema\Configuration;

use OpenApi\Attributes\Items;
use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Schema\SelectOption;
use Pimcore\Bundle\StudioBackendBundle\Util\Schema\AdditionalAttributesInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\AdditionalAttributesTrait;

#[Schema(
    schema: 'ClassificationStoreConfigurationKeyDetail',
    title: 'Classification Store Configuration Key Detail',
    required: [
        'id', 'name', 'storeId', 'type', 'enabled', 'description',
        'definition', 'creationDate', 'modificationDate',
    ],
    type: 'object'
)]
final class KeyDetail implements AdditionalAttributesInterface
{
    use AdditionalAttributesTrait;

    public function __construct(
        #[Property(description: 'ID of the key', type: 'integer', example: 1)]
        private readonly int $id,
        #[Property(description: 'Name of the key', type: 'string', example: 'My Key')]
        private readonly string $name,
        #[Property(description: 'ID of the store this key belongs to', type: 'integer', example: 1)]
        private readonly int $storeId,
        #[Property(description: 'Data type of the key', type: 'string', example: 'input')]
        private readonly string $type,
        #[Property(description: 'Whether the key is enabled', type: 'boolean', example: true)]
        private readonly bool $enabled,
        #[Property(description: 'Description of the key', type: 'string', example: 'Key description', nullable: true)]
        private readonly ?string $description = null,
        #[Property(
            description: 'Stored key definition; "indexed" is its search index flag,'
                . ' "variants" applies to "multiValue" keys',
            type: 'object',
            example: ['fieldtype' => 'input', 'name' => 'weight', 'title' => 'Weight', 'indexed' => true],
            nullable: true
        )]
        private readonly ?array $definition = [],
        #[Property(
            description: 'Creation date as Unix timestamp',
            type: 'integer',
            example: 1734567890,
            nullable: true
        )]
        private readonly ?int $creationDate = null,
        #[Property(
            description: 'Modification date as Unix timestamp',
            type: 'integer',
            example: 1734567890,
            nullable: true
        )]
        private readonly ?int $modificationDate = null,
        #[Property(description: 'Title of the key', type: 'string', example: 'My Key Title', nullable: true)]
        private readonly ?string $title = null,
        #[Property(
            description: 'Opaque identifier of the key, assigned by the system and never writable',
            type: 'string',
            example: '01K9T0ZQ0VX8W4S1R7J3A2B5C6',
            nullable: true
        )]
        private readonly ?string $identifier = null,
        #[Property(
            description: 'Per-language flag as the key stores it; null defers to the class definition field',
            type: 'boolean',
            example: true,
            nullable: true
        )]
        private readonly ?bool $localized = null,
        #[Property(
            description: 'Free-form pairs the key carries, as stored; null where it holds none',
            type: 'object',
            example: ['source' => 'erp'],
            nullable: true
        )]
        private readonly ?array $customMetadata = null,
        #[Property(
            description: 'Resolved options of a select or multiselect key for this request; null otherwise',
            type: 'array',
            items: new Items(ref: SelectOption::class),
            example: [['key' => 'Red', 'value' => 'red']],
            nullable: true
        )]
        private readonly ?array $options = null,
        #[Property(
            description: 'Why options is null for a select key: providerNotStatic or resolutionFailed;'
                . ' null if nothing failed',
            type: 'string',
            example: 'providerNotStatic',
            nullable: true
        )]
        private readonly ?string $optionsUnavailableReason = null,
        #[Property(
            description: 'Unit vocabulary of the key\'s own definition; null where its type holds no units',
            ref: KeyUnits::class,
            nullable: true
        )]
        private readonly ?KeyUnits $units = null,
    ) {
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getStoreId(): int
    {
        return $this->storeId;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getDefinition(): ?array
    {
        return $this->definition;
    }

    public function getCreationDate(): ?int
    {
        return $this->creationDate;
    }

    public function getModificationDate(): ?int
    {
        return $this->modificationDate;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function getIdentifier(): ?string
    {
        return $this->identifier;
    }

    public function getLocalized(): ?bool
    {
        return $this->localized;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getCustomMetadata(): ?array
    {
        return $this->customMetadata;
    }

    /**
     * @return SelectOption[]|null
     */
    public function getOptions(): ?array
    {
        return $this->options;
    }

    public function getOptionsUnavailableReason(): ?string
    {
        return $this->optionsUnavailableReason;
    }

    public function getUnits(): ?KeyUnits
    {
        return $this->units;
    }
}
