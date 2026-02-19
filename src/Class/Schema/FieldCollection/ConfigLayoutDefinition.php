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

namespace Pimcore\Bundle\StudioBackendBundle\Class\Schema\FieldCollection;

use OpenApi\Attributes\Items;
use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;
use Pimcore\Bundle\StudioBackendBundle\Util\Schema\AdditionalAttributesInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\AdditionalAttributesTrait;

/**
 * @internal
 */
#[Schema(
    schema: 'FieldCollectionConfigLayoutDefinition',
    title: 'Field Collection Config Layout Definition',
    required: [
        'name',
        'type',
        'region',
        'title',
        'width',
        'height',
        'collapsible',
        'collapsed',
        'bodyStyle',
        'datatype',
        'children',
        'locked',
        'fieldtype',
        'layout',
        'border',
        'icon',
        'labelWidth',
        'labelAlign',
    ],
    type: 'object'
)]
final class ConfigLayoutDefinition implements AdditionalAttributesInterface
{
    use AdditionalAttributesTrait;

    public function __construct(
        #[Property(description: 'Name', type: 'string', example: null, nullable: true)]
        private readonly ?string $name,
        #[Property(description: 'Type', type: 'string', example: null, nullable: true)]
        private readonly ?string $type,
        #[Property(description: 'Region', type: 'string', example: null, nullable: true)]
        private readonly ?string $region,
        #[Property(description: 'Title', type: 'string', example: null, nullable: true)]
        private readonly ?string $title,
        #[Property(description: 'Width', type: 'integer', example: 0)]
        private readonly int $width,
        #[Property(description: 'Height', type: 'integer', example: 0)]
        private readonly int $height,
        #[Property(description: 'Collapsible', type: 'boolean', example: false)]
        private readonly bool $collapsible,
        #[Property(description: 'Collapsed', type: 'boolean', example: false)]
        private readonly bool $collapsed,
        #[Property(description: 'Body Style', type: 'string', example: null, nullable: true)]
        private readonly ?string $bodyStyle,
        #[Property(description: 'Data Type', type: 'string', example: 'layout')]
        private readonly string $datatype,
        #[Property(description: 'Children', type: 'array', items: new Items(type: 'object'), example: [])]
        private readonly array $children,
        #[Property(description: 'Locked', type: 'boolean', example: false)]
        private readonly bool $locked,
        #[Property(description: 'Field Type', type: 'string', example: 'panel')]
        private readonly string $fieldtype,
        #[Property(description: 'Layout', type: 'string', example: null, nullable: true)]
        private readonly ?string $layout,
        #[Property(description: 'Border', type: 'boolean', example: false)]
        private readonly bool $border,
        #[Property(description: 'Icon', type: 'string', example: null, nullable: true)]
        private readonly ?string $icon,
        #[Property(description: 'Label Width', type: 'integer', example: 100)]
        private readonly int $labelWidth,
        #[Property(description: 'Label Align', type: 'string', example: 'left')]
        private readonly string $labelAlign,
    ) {
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function getRegion(): ?string
    {
        return $this->region;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function getWidth(): int
    {
        return $this->width;
    }

    public function getHeight(): int
    {
        return $this->height;
    }

    public function isCollapsible(): bool
    {
        return $this->collapsible;
    }

    public function isCollapsed(): bool
    {
        return $this->collapsed;
    }

    public function getBodyStyle(): ?string
    {
        return $this->bodyStyle;
    }

    public function getDatatype(): string
    {
        return $this->datatype;
    }

    public function getChildren(): array
    {
        return $this->children;
    }

    public function isLocked(): bool
    {
        return $this->locked;
    }

    public function getFieldtype(): string
    {
        return $this->fieldtype;
    }

    public function getLayout(): ?string
    {
        return $this->layout;
    }

    public function isBorder(): bool
    {
        return $this->border;
    }

    public function getIcon(): ?string
    {
        return $this->icon;
    }

    public function getLabelWidth(): int
    {
        return $this->labelWidth;
    }

    public function getLabelAlign(): string
    {
        return $this->labelAlign;
    }
}
