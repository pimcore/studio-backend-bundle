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
                anyOf: [
                    new Schema(ref: RelationFieldConfig::class),
                    new Schema(ref: SimpleFieldConfig::class),
                    new Schema(ref: StaticTextConfig::class),
                ]
            ),
            example: [['field' => 'name', 'relation' => 'manufacturer'], ['field' => 'name'], ['text' => 'name']])]
        private array $advancedColumn,
    ) {
    }

    /**
     * @return RelationFieldConfig[]|SimpleFieldConfig[]|StaticTextConfig[]
     */
    public function getColumns(): array
    {
        return $this->advancedColumn;
    }
}
