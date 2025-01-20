<?php
declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Bundle\StudioBackendBundle\DataObject\Schema;

use OpenApi\Attributes\Items;
use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;
use Pimcore\Bundle\StudioBackendBundle\Response\ElementIcon;
use Pimcore\Bundle\StudioBackendBundle\Util\Schema\AdditionalAttributesInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\AdditionalAttributesTrait;

#[Schema(
    title: 'Layout',
    required: [
        'name',
        'dataType',
        'fieldType',
        'type',
        'layout',
        'region',
        'title',
        'width',
        'height',
        'collapsible',
        'collapsed',
        'bodyStyle',
        'locked',
        'children',
        'icon',
        'labelAlign',
        'labelWidth',
        'border',
    ],
    type: 'object'
)]
class Layout implements AdditionalAttributesInterface
{
    use AdditionalAttributesTrait;

    public function __construct(
        #[Property(description: 'Name', type: 'string', example: 'pimcore_root')]
        private readonly string $name,
        #[Property(description: 'Data Type', type: 'string', example: 'layout')]
        private readonly string $dataType,
        #[Property(description: 'Field Type', type: 'string', example: 'panel')]
        private readonly string $fieldType,
        #[Property(description: 'Type', type: 'string', example: null)]
        private readonly ?string $type = null,
        #[Property(description: 'Layout', type: 'string', example: null)]
        private readonly ?string $layout = null,
        #[Property(description: 'Region', type: 'string', example: 'center')]
        private readonly ?string $region = null,
        #[Property(description: 'Title', type: 'string', example: 'MyLayout')]
        private readonly ?string $title = null,
        #[Property(description: 'Width', type: 'integer', example: 0)]
        private readonly int $width = 0,
        #[Property(description: 'Height', type: 'integer', example: 0)]
        private readonly int $height = 0,
        #[Property(description: 'Collapsible', type: 'bool', example: false)]
        private readonly bool $collapsible = false,
        #[Property(description: 'Collapsed', type: 'bool', example: false)]
        private readonly bool $collapsed = false,
        #[Property(description: 'Body Style', type: 'string', example: '(float: left;)')]
        private readonly ?string $bodyStyle = null,
        #[Property(description: 'Locked', type: 'bool', example: false)]
        private readonly bool $locked = false,
        #[Property(description: 'Children', type: 'array', items: new Items(type: 'object'), example: '[{id: 1}]')]
        private readonly array $children = [],
        #[Property(description: 'Icon', type: ElementIcon::class)]
        private readonly ?ElementIcon $icon = null,
        #[Property(description: 'Label Align', type: 'string', example: 'left')]
        private readonly string $labelAlign = 'left',
        #[Property(description: 'Label Width', type: 'integer', example: 100)]
        private readonly int $labelWidth = 100,
        #[Property(description: 'Border', type: 'bool', example: false)]
        private readonly bool $border = false,
    ) {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function getDataType(): string
    {
        return $this->dataType;
    }

    public function getFieldType(): string
    {
        return $this->fieldType;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function getLayout(): ?string
    {
        return $this->layout;
    }

    public function getRegion(): ?string
    {
        return $this->region;
    }

    public function getWidth(): int
    {
        return $this->width;
    }

    public function getHeight(): int
    {
        return $this->height;
    }

    public function getCollapsible(): bool
    {
        return $this->collapsible;
    }

    public function getCollapsed(): bool
    {
        return $this->collapsed;
    }

    public function getBodyStyle(): ?string
    {
        return $this->bodyStyle;
    }

    public function getBorder(): bool
    {
        return $this->border;
    }

    public function getIcon(): ?ElementIcon
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

    public function getLocked(): bool
    {
        return $this->locked;
    }

    public function getChildren(): array
    {
        return $this->children;
    }
}
