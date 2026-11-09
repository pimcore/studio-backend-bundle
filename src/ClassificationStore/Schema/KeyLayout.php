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
            description: 'Whether the key holds a value per language, as the key stores it: true, false, or null'
                . ' where the key states nothing and the `localized` flag of the class definition field the key'
                . ' is rendered in decides. It is not resolved against that field here. The flag is not yet'
                . ' honoured by the object data endpoints, which still read and write a classification store'
                . ' field under the language set of the class definition field alone.',
            type: 'boolean',
            example: true,
            nullable: true
        )]
        private ?bool $localized = null,
        #[Property(
            description: 'The variant names the key permits, in the order it declares them, as the key stores them;'
                . ' null where the key declares none. A value carrying no variant addresses the default variant,'
                . ' which is never one of these names.',
            type: 'array',
            items: new Items(type: 'string'),
            example: ['metric', 'imperial'],
            nullable: true
        )]
        private ?array $variants = null,
        #[Property(
            description: 'Whether the key holds more than one value per variant, as the key stores it; null where'
                . ' the key declares nothing.',
            type: 'boolean',
            example: true,
            nullable: true
        )]
        private ?bool $multiValue = null,
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

    public function getMultiValue(): ?bool
    {
        return $this->multiValue;
    }
}
