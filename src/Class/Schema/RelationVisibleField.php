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
use Pimcore\Bundle\StudioBackendBundle\Util\Schema\AdditionalAttributesInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\AdditionalAttributesTrait;

/**
 * A field that can be shown as a visible field of a many-to-many relation or advanced many-to-many
 * relation, as described by the relation's getAvailableVisibleFields().
 */
#[Schema(
    schema: 'RelationVisibleField',
    title: 'Relation Visible Field',
    required: ['name', 'title', 'fieldtype', 'sources', 'localized'],
    type: 'object'
)]
final class RelationVisibleField implements AdditionalAttributesInterface
{
    use AdditionalAttributesTrait;

    /**
     * @param string[] $sources
     * @param array<int, array<string, mixed>>|null $options
     */
    public function __construct(
        #[Property(description: 'Field name', type: 'string', example: 'title')]
        private readonly string $name,
        #[Property(description: 'Field title', type: 'string', example: 'Title')]
        private readonly string $title,
        #[Property(description: 'Field type', type: 'string', example: 'input')]
        private readonly string $fieldtype,
        #[Property(
            description: 'Where the field comes from: "object" for every object, "object:<ClassName>" for the ' .
            'fields of a class, "asset" or "document"',
            type: 'array',
            items: new Items(type: 'string'),
            example: ['object:Product', 'asset']
        )]
        private readonly array $sources,
        #[Property(description: 'Whether the field is a localized field of a class', type: 'boolean', example: false)]
        private readonly bool $localized = false,
        #[Property(
            description: 'Type of the predefined asset metadata the field comes from',
            type: 'string',
            example: 'input',
            nullable: true
        )]
        private readonly ?string $metadataType = null,
        #[Property(
            description: 'Language of the predefined asset metadata the field comes from',
            type: 'string',
            example: 'en',
            nullable: true
        )]
        private readonly ?string $language = null,
        #[Property(
            description: 'Options of select fields',
            type: 'array',
            items: new Items(type: 'object'),
            example: [['key' => 'Red', 'value' => 'red']],
            nullable: true
        )]
        private readonly ?array $options = null,
        #[Property(
            description: 'Options provider class of select fields',
            type: 'string',
            example: null,
            nullable: true
        )]
        private readonly ?string $optionsProviderClass = null,
    ) {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getFieldtype(): string
    {
        return $this->fieldtype;
    }

    /**
     * @return string[]
     */
    public function getSources(): array
    {
        return $this->sources;
    }

    public function isLocalized(): bool
    {
        return $this->localized;
    }

    public function getMetadataType(): ?string
    {
        return $this->metadataType;
    }

    public function getLanguage(): ?string
    {
        return $this->language;
    }

    /**
     * @return array<int, array<string, mixed>>|null
     */
    public function getOptions(): ?array
    {
        return $this->options;
    }

    public function getOptionsProviderClass(): ?string
    {
        return $this->optionsProviderClass;
    }
}
