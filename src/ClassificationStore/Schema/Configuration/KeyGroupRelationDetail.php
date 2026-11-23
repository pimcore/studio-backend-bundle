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
    schema: 'ClassificationStoreConfigurationKeyGroupRelationDetail',
    title: 'Classification Store Configuration Key Group Relation Detail',
    required: ['keyId', 'groupId', 'sorter', 'mandatory', 'keyName', 'keyDescription', 'groupName'],
    type: 'object'
)]
final class KeyGroupRelationDetail implements AdditionalAttributesInterface
{
    use AdditionalAttributesTrait;

    public function __construct(
        #[Property(description: 'ID of the key', type: 'integer', example: 1)]
        private readonly int $keyId,
        #[Property(description: 'ID of the group', type: 'integer', example: 1)]
        private readonly int $groupId,
        #[Property(description: 'Sort order of the relation', type: 'integer', example: 0)]
        private readonly int $sorter,
        #[Property(description: 'Whether the key is mandatory in this group', type: 'boolean', example: false)]
        private readonly bool $mandatory,
        #[Property(description: 'Name of the key', type: 'string', example: 'My Key', nullable: true)]
        private readonly ?string $keyName = null,
        #[Property(description: 'Description of the key', type: 'string', example: 'Key description', nullable: true)]
        private readonly ?string $keyDescription = null,
        #[Property(description: 'Name of the group', type: 'string', example: 'My Group', nullable: true)]
        private readonly ?string $groupName = null,
        #[Property(
            description: 'Identifier of the sub group this assignment carries',
            type: 'string',
            example: '01JBQ8X9Z0ABCDEFGHJKMNPQRS',
            nullable: true
        )]
        private readonly ?string $subGroup = null,
        #[Property(
            description: 'Partial field definition overriding the one of the key for this assignment',
            type: 'object',
            example: ['defaultUnit' => 'kg'],
            nullable: true
        )]
        private readonly ?array $definitionOverride = null,
        #[Property(
            description: 'Free-form metadata carried by this assignment, reported as it is stored. No member is'
                . ' reserved.',
            type: 'object',
            example: ['source' => 'erp'],
            nullable: true
        )]
        private readonly ?array $metadata = null,
        #[Property(
            // Null means the key states nothing and the class definition field's own `localized` flag
            // decides. The flag belongs to the key, is not resolved against that field here, and the object
            // data endpoints do not honour it yet: they read and write under the field's language set alone.
            description: 'Whether the key holds a value per language; null lets the class definition field decide',
            type: 'boolean',
            example: true,
            nullable: true
        )]
        private readonly ?bool $localized = null,
        #[Property(
            description: 'Data type of the key as stored, or null where the key configuration is gone',
            type: 'string',
            example: 'quantityValue',
            nullable: true
        )]
        private readonly ?string $keyType = null,
        #[Property(
            // Stored options or a static provider's, in resolution order, narrowed by definitionOverride.
            // Null for a key that is no select or multiselect; optionsUnavailableReason says why otherwise.
            // The stored definition stays authoritative for storage.
            description: 'This assignment\'s resolved option vocabulary, narrowed by its definitionOverride',
            type: 'array',
            items: new Items(ref: SelectOption::class),
            example: [['key' => 'Alloy', 'value' => 'alloy']],
            nullable: true
        )]
        private readonly ?array $options = null,
        #[Property(
            // providerNotStatic: the options provider's options depend on a data object. resolutionFailed:
            // the provider could not be resolved or raised, for instance a deleted select options configuration.
            description: 'Why options is null for a select or multiselect key; null means nothing failed',
            type: 'string',
            example: 'providerNotStatic',
            nullable: true
        )]
        private readonly ?string $optionsUnavailableReason = null,
        #[Property(
            // The key's own definition with definitionOverride applied; null where its type holds no units.
            // A null validUnits restricts nothing, which the type tells apart from having no units at all.
            description: 'This assignment\'s unit vocabulary, or null where the key\'s type holds no units',
            ref: KeyUnits::class,
            nullable: true
        )]
        private readonly ?KeyUnits $units = null,
        #[Property(
            // What an editor of the assignment offers, where `options` is the subset chosen. Null for a key
            // that is no select or multiselect; keyOptionsUnavailableReason says why otherwise.
            description: 'The key\'s own option vocabulary, not narrowed by this assignment',
            type: 'array',
            items: new Items(ref: SelectOption::class),
            example: [['key' => 'Alloy', 'value' => 'alloy']],
            nullable: true
        )]
        private readonly ?array $keyOptions = null,
        #[Property(
            // The values of optionsUnavailableReason.
            description: 'Why keyOptions is null for a select or multiselect key; null means nothing failed',
            type: 'string',
            example: 'providerNotStatic',
            nullable: true
        )]
        private readonly ?string $keyOptionsUnavailableReason = null,
        #[Property(
            // What an editor of the assignment offers, where `units` is the subset chosen.
            description: 'The key\'s own unit vocabulary, or null where its type holds no units',
            ref: KeyUnits::class,
            nullable: true
        )]
        private readonly ?KeyUnits $keyUnits = null,
    ) {
    }

    public function getKeyId(): int
    {
        return $this->keyId;
    }

    public function getGroupId(): int
    {
        return $this->groupId;
    }

    public function getSorter(): int
    {
        return $this->sorter;
    }

    public function isMandatory(): bool
    {
        return $this->mandatory;
    }

    public function getKeyName(): ?string
    {
        return $this->keyName;
    }

    public function getKeyDescription(): ?string
    {
        return $this->keyDescription;
    }

    public function getGroupName(): ?string
    {
        return $this->groupName;
    }

    public function getSubGroup(): ?string
    {
        return $this->subGroup;
    }

    public function getDefinitionOverride(): ?array
    {
        return $this->definitionOverride;
    }

    public function getMetadata(): ?array
    {
        return $this->metadata;
    }

    public function getLocalized(): ?bool
    {
        return $this->localized;
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

    public function getKeyType(): ?string
    {
        return $this->keyType;
    }

    /**
     * @return SelectOption[]|null
     */
    public function getKeyOptions(): ?array
    {
        return $this->keyOptions;
    }

    public function getKeyOptionsUnavailableReason(): ?string
    {
        return $this->keyOptionsUnavailableReason;
    }

    public function getKeyUnits(): ?KeyUnits
    {
        return $this->keyUnits;
    }
}
