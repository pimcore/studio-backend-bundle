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

namespace Pimcore\Bundle\StudioBackendBundle\Grid\Schema;

use OpenApi\Attributes\Items;
use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;
use Pimcore\Bundle\StudioBackendBundle\Util\Schema\AdditionalAttributesInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\AdditionalAttributesTrait;

/**
 * The visible field values of one row of a many-to-many relation or advanced many-to-many relation.
 */
#[Schema(
    schema: 'RelationVisibleFieldValues',
    title: 'Relation Visible Field Values',
    required: ['type', 'id', 'columns'],
    type: 'object'
)]
final class RelationVisibleFieldValues implements AdditionalAttributesInterface
{
    use AdditionalAttributesTrait;

    /**
     * @param ColumnData[] $columns
     */
    public function __construct(
        #[Property(description: 'Element type of the related element', type: 'string', example: 'asset')]
        private readonly string $type,
        #[Property(description: 'Id of the related element', type: 'integer', example: 83)]
        private readonly int $id,
        #[Property(
            description: 'One entry per visible field; the value is null where the field does not apply',
            type: 'array',
            items: new Items(ref: ColumnData::class)
        )]
        private readonly array $columns,
    ) {
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return ColumnData[]
     */
    public function getColumns(): array
    {
        return $this->columns;
    }
}
