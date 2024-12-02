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
    schema: 'FieldCollectionLayoutDefinition',
    title: 'Field Collection Layout Definition',
    required: [
        'key',
        'title',
        'width',
        'height',
        'collapsible',
        'collapsed',
        'datatype',
        'children',
    ],
    type: 'object'
)]
final class LayoutDefinition implements AdditionalAttributesInterface
{
    use AdditionalAttributesTrait;

    public function __construct(
        #[Property(description: 'Key of Field Collection', type: 'string')]
        private readonly string $key,
        #[Property(description: 'Data Type', type: 'string', example: 'layout')]
        private readonly string $datatype,
        #[Property(description: 'Group', type: 'string', example: 'Group Name')]
        private readonly ?string $group = null,
        #[Property(description: 'Name', type: 'string', example: 'Layout')]
        private readonly ?string $name = null,
        #[Property(description: 'Type', type: 'string')]
        private readonly ?string $type = null,
        #[Property(description: 'Region', type: 'string')]
        private readonly ?string $region = null,
        #[Property(description: 'Title', type: 'string')]
        private readonly ?string $title = null,
        #[Property(description: 'Width', type: 'integer', example: 0)]
        private readonly int $width = 0,
        #[Property(description: 'Height', type: 'integer', example: 0)]
        private readonly int $height = 0,
        #[Property(description: 'Collapsible', type: 'boolean', example: false)]
        private readonly bool $collapsible = false,
        #[Property(description: 'collapsed', type: 'boolean', example: false)]
        private readonly bool $collapsed = false,
        #[Property(description: 'Children', type: 'array', items: new Items(), example: '[]')]
        private readonly array $children = [],
    ) {
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function getGroup(): ?string
    {
        return $this->group;
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

    public function getDatatype(): string
    {
        return $this->datatype;
    }

    public function getChildren(): array
    {
        return $this->children;
    }
}
