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

namespace Pimcore\Bundle\StudioBackendBundle\Element\Schema;

use OpenApi\Attributes\Items;
use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;
use Pimcore\Bundle\StudioBackendBundle\Util\Schema\AdditionalAttributesInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\AdditionalAttributesTrait;

#[Schema(
    title: 'Element Location Data',
    required: ['widgetId', 'treeLevelData'],
    type: 'object'
)]
final class LocationData implements AdditionalAttributesInterface
{
    use AdditionalAttributesTrait;

    public function __construct(
        #[Property(description: 'Widget Id', type: 'string', example: 'd061699e_da42_4075_b504_c2c93c687819')]
        private readonly string $widgetId,
        #[Property(
            description: 'Tree level data',
            type: 'array',
            items: new Items(ref: TreeLevelData::class, type: 'object')
        )]
        private readonly array $treeLevelData
    ) {
    }

    public function getWidgetId(): string
    {
        return $this->widgetId;
    }

    /**
     * @return TreeLevelData[]
     */
    public function getTreeLevelData(): array
    {
        return $this->treeLevelData;
    }
}
