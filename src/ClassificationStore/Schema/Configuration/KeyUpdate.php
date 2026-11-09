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

use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Util\Constant\AbsentValue;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;

/**
 * @internal
 */
#[Schema(
    schema: 'ClassificationStoreConfigurationKeyUpdate',
    title: 'Classification Store Configuration Key Update',
    required: ['name', 'type', 'definition'],
    type: 'object'
)]
final readonly class KeyUpdate
{
    /**
     * @throws InvalidArgumentException
     */
    public function __construct(
        #[Property(description: 'Name of the key', type: 'string', example: 'My Key')]
        private string $name,
        #[Property(description: 'Title of the key', type: 'string', example: 'My Key Title', nullable: true)]
        private ?string $title = null,
        #[Property(description: 'Description of the key', type: 'string', example: 'Key description', nullable: true)]
        private ?string $description = null,
        #[Property(
            description: 'Data type of the key (e.g. input, textarea, select)',
            type: 'string',
            example: 'input',
            nullable: true
        )]
        private ?string $type = null,
        #[Property(
            description: 'Values for object brick definition metadata. The member "indexed" is reserved: it is this'
                . ' key\'s own search index flag, where false asks for the key to be left out of the search index'
                . ' wherever it is used and anything else leaves it indexed. Changing it queues a remap of every'
                . ' search index the key\'s store reaches, which converges in the background; honouring the flag is'
                . ' the search index\'s own. The members "variants" and "multiValue" are reserved as well:'
                . ' "variants" is the ordered list of variant names the key permits a value to carry, where a value'
                . ' carrying no variant addresses the default variant, and "multiValue" is whether the key holds'
                . ' more than one value per variant. A key that permits more'
                . ' than the one slot it always had - a non-empty "variants" list, or "multiValue" true - carries'
                . ' its values in the object data payload as a list of entries rather than as a single value, while'
                . ' a key declaring an empty "variants" list or "multiValue" false permits exactly that one slot and'
                . ' its values are carried unchanged. Declaring either is accepted whatever the engine of the key\'s'
                . ' store reports. The whole object replaces the stored one, so a client that rebuilds it'
                . ' from a form has to send the member back to keep it. It is unrelated to the "index" member below,'
                . ' which is about the object query tables.',
            type: 'object',
            example: [
                'name' => 'weight',
                'indexed' => false,
                'datatype' => 'data',
                'fieldtype' => 'quantityValue',
                'title' => 'Weight',
                'tooltip' => '',
                'mandatory' => false,
                'index' => false,
                'noteditable' => false,
                'invisible' => false,
                'visibleGridView' => false,
                'visibleSearch' => false,
                'style' => '',
                'width' => '',
                'unitWidth' => '',
                'defaultValue' => null,
                'defaultUnit' => null,
                'defaultValueGenerator' => '',
                'validUnits' => [
                    'kg',
                ],
                'autoConvert' => false,
                'decimalSize' => null,
                'decimalPrecision' => null,
                'integer' => false,
                'unsigned' => false,
                'minValue' => null,
                'maxValue' => null,
                'displayfield-1596-inputEl' => 'The width of this component.',
            ],
            nullable: true
        )]
        private ?array $definition = null,
        #[Property(
            description: 'Whether the key holds a value per language: true, false, or null to state nothing and'
                . ' let the `localized` flag of the class definition field the key is rendered in decide. Leaving'
                . ' the member out of the request keeps the stored value, sending it as null stores null. The'
                . ' flag is not yet honoured by the object data endpoints, which still read and write a'
                . ' classification store field under the language set of the class definition field alone.',
            type: 'boolean',
            example: true,
            nullable: true
        )]
        private ?bool $localized = null,
        // Set by KeyUpdateDenormalizer: an absent field leaves the stored value alone, a present empty one clears it.
        private bool $titleSupplied = false,
        private bool $descriptionSupplied = false,
        private bool $localizedSupplied = false,
        // only refused here, never a property of this schema; the default stands for an absent key
        mixed $identifier = AbsentValue::ABSENT,
    ) {
        if (empty($this->name)) {
            throw new InvalidArgumentException('Name cannot be empty.');
        }

        if ($identifier !== AbsentValue::ABSENT) {
            throw new InvalidArgumentException('Identifier is assigned by the system and cannot be set.');
        }
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function getDefinition(): ?array
    {
        return $this->definition;
    }

    public function getLocalized(): ?bool
    {
        return $this->localized;
    }

    /**
     * A non-null title counts as supplied on its own, so constructing this DTO directly — without
     * the denormalizer that sets the flag — cannot silently drop the value. The flag is what tells
     * a title supplied as null apart from an omitted one, which inference cannot recover.
     */
    public function isTitleSupplied(): bool
    {
        return $this->titleSupplied || $this->title !== null;
    }

    public function isDescriptionSupplied(): bool
    {
        return $this->descriptionSupplied || $this->description !== null;
    }

    public function isLocalizedSupplied(): bool
    {
        return $this->localizedSupplied || $this->localized !== null;
    }

    /**
     * @throws InvalidArgumentException
     */
    public function withDefinition(array $definition): self
    {
        return new self(
            name: $this->name,
            title: $this->title,
            description: $this->description,
            type: $this->type,
            definition: $definition,
            localized: $this->localized,
            titleSupplied: $this->titleSupplied,
            descriptionSupplied: $this->descriptionSupplied,
            localizedSupplied: $this->localizedSupplied,
        );
    }
}
