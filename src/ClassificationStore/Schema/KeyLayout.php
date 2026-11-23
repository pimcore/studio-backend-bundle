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

namespace Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Schema;

use OpenApi\Attributes\Items;
use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\ClassDefinition\Data\EncryptedField;

/**
 * @internal
 */
#[Schema(
    title: 'Classification Store Collection',
    required: [
        'id',
        'name',
        'description',
        'definition',
    ],
    type: 'object'
)]
final readonly class KeyLayout
{
    public function __construct(
        #[Property(description: 'ID', type: 'integer', example: 42)]
        private int $id,
        #[Property(description: 'Name', type: 'string', example: 'value')]
        private string $name,
        #[Property(description: 'Description', type: 'string', example: 'value')]
        private string $description,
        #[Property(description: 'Layout Definition', type: 'object')]
        private EncryptedField|Data $definition,
        #[Property(description: 'Resolved label of the key', type: 'string', example: 'value', nullable: true)]
        private ?string $title = null,
        #[Property(
            description: 'Identifier of the sub group this key is assigned to within the group',
            type: 'string',
            example: '01JBQ8X9Z0ABCDEFGHJKMNPQRS',
            nullable: true
        )]
        private ?string $subGroup = null,
        #[Property(
            description: 'Per language; null defers to the class definition field.'
                . ' It is not resolved against that field here.',
            type: 'boolean',
            example: true,
            nullable: true
        )]
        private ?bool $localized = null,
        #[Property(
            description: 'Variants a `multiValue` key permits; null for a key of any other type',
            type: 'array',
            items: new Items(ref: VariantLayout::class),
            example: [['name' => 'metric', 'label' => 'Metric']],
            nullable: true
        )]
        private ?array $variants = null,
        #[Property(
            description: 'Whether a `multiValue` key permits a value with no variant; null for any other type',
            type: 'boolean',
            example: true,
            nullable: true
        )]
        private ?bool $defaultVariant = null,
        #[Property(
            description: 'Default variant label of a `multiValue` key;'
                . ' null where it declares none or for other types',
            type: 'string',
            example: 'Standard',
            nullable: true
        )]
        private ?string $defaultVariantLabel = null,
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

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getDefinition(): EncryptedField|Data
    {
        return $this->definition;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function getSubGroup(): ?string
    {
        return $this->subGroup;
    }

    public function getLocalized(): ?bool
    {
        return $this->localized;
    }

    public function getVariants(): ?array
    {
        return $this->variants;
    }

    public function getDefaultVariant(): ?bool
    {
        return $this->defaultVariant;
    }

    public function getDefaultVariantLabel(): ?string
    {
        return $this->defaultVariantLabel;
    }
}
