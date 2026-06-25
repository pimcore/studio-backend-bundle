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

namespace Pimcore\Bundle\StudioBackendBundle\Grid\Schema\AdvancedColumnConfig;

use OpenApi\Attributes\Items;
use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;

/**
 * @internal
 */
#[Schema(
    title: 'Advanced Column Config',
    required: ['advancedColumns'],
    type: 'object'
)]
final readonly class AdvancedColumnConfig
{
    public function __construct(
        #[Property(
            description: 'advancedColumns',
            type: 'array',
            items: new Items(
                required: ['key', 'config'],
                properties: [
                    new Property(
                        property: 'key',
                        description: 'Type of the column, e.g. "simpleField", "relationField", "staticText"',
                        type: 'string',
                        example: 'simpleField'
                    ),
                    new Property(
                        property: 'config',
                        type: 'array',
                        items: new Items(
                            anyOf: [
                                new Schema(ref: RelationFieldConfig::class),
                                new Schema(ref: SimpleFieldConfig::class),
                                new Schema(ref: StaticTextConfig::class),
                            ]
                        ),
                        example: [
                            'field' => 'name',
                            'relation' => 'manufacturer',
                        ]
                    ),
                ]
            ),
            example: [
                [
                    'key' => 'simpleField',
                    'config' => ['field' => 'name'],
                ],
            ]
        )]
        private array $advancedColumns,
        #[Property(
            description: 'List if Transformers that should be applied',
            type: 'array',
            items: new Items(ref: Transformer::class))
        ]
        private array $transformers,

    ) {
    }

    /**
     * @return array<RelationFieldConfig|SimpleFieldConfig|StaticTextConfig>
     */
    public function getColumns(): array
    {
        return $this->advancedColumns;
    }

    /**
     * @return Transformer[]
     */
    public function getTransformers(): array
    {
        return $this->transformers;
    }
}
