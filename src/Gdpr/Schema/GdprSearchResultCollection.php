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

namespace Pimcore\Bundle\StudioBackendBundle\Gdpr\Schema;

use OpenApi\Attributes\Items;
use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;
use Pimcore\Bundle\StudioBackendBundle\Util\Schema\AdditionalAttributesInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\AdditionalAttributesTrait;

/**
 * @internal
 */
#[Schema(
    title: 'GDPR Search Result Collection',
    description: 'A collection of search results from all providers.',
    required: ['items'],
    type: 'object'
)]

final class GdprSearchResultCollection implements AdditionalAttributesInterface
{
    use AdditionalAttributesTrait;

    /**
     * @param array<GdprSearchResult> $items
     */
    public function __construct(
        #[Property(
            description: 'List of search results, grouped by provider',
            type: 'array',
            items: new Items(ref: GdprSearchResult::class)
        )]
        private readonly array $items,

        #[Property(
            description: 'Total number of items across all pages',
            type: 'integer'
        )]
        private readonly int $totalItems,
    ) {}

    /**
     * @return array<GdprSearchResult>
     */
    public function getItems(): array
    {
        return $this->items;
    }

    public function getTotalItems(): int
    {
        return $this->totalItems;
    }
}
