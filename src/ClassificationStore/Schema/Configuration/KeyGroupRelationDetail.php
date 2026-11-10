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
            description: 'Whether the key holds a value per language, as the key stores it: true, false, or null'
                . ' where the key states nothing and the `localized` flag of the class definition field the key'
                . ' is rendered in decides. It belongs to the key, not to this assignment, and is not resolved'
                . ' against that field here. The flag is not yet honoured by the object data endpoints, which'
                . ' still read and write a classification store field under the language set of the class'
                . ' definition field alone.',
            type: 'boolean',
            example: true,
            nullable: true
        )]
        private readonly ?bool $localized = null,
        #[Property(
            description: 'Data type of the key, as the key configuration stores it, or null where the key'
                . ' configuration is gone',
            type: 'string',
            example: 'quantityValue',
            nullable: true
        )]
        private readonly ?string $keyType = null,
        #[Property(
            description: 'The resolved view of this key\'s option vocabulary for this request, whatever its source:'
                . ' its own stored options, or the options of an options provider that reports static options,'
                . ' in the order they are resolved. Null where the key is not a select or multiselect, and where'
                . ' optionsUnavailableReason says why not. The stored definition remains authoritative for'
                . ' storage.',
            type: 'array',
            items: new Items(ref: SelectOption::class),
            nullable: true
        )]
        private readonly ?array $options = null,
        #[Property(
            description: 'Why options is null for a select or multiselect key: "providerNotStatic" where its'
                . ' options provider reports options that depend on a data object, "resolutionFailed" where the'
                . ' provider could not be resolved or raised, for instance for a deleted select options'
                . ' configuration. Null means nothing failed.',
            type: 'string',
            example: 'providerNotStatic',
            nullable: true
        )]
        private readonly ?string $optionsUnavailableReason = null,
        #[Property(
            description: 'The unit vocabulary of the key\'s own definition, never of an assignment override, and'
                . ' null where its type holds no units. Where validUnits is null the definition restricts nothing;'
                . ' the type tells that apart from a type with no units at all.',
            ref: KeyUnits::class,
            nullable: true
        )]
        private readonly ?KeyUnits $units = null,
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
}
