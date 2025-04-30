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

namespace Pimcore\Bundle\StudioBackendBundle\Listing\Filter\Loader;

use Pimcore\Bundle\StudioBackendBundle\Listing\Filter\FilterInterface;
use Pimcore\Bundle\StudioBackendBundle\Listing\Filter\FilterLoaderInterface;
use Pimcore\Bundle\StudioBackendBundle\Listing\Filter\Filters;
use Symfony\Component\DependencyInjection\Attribute\TaggedIterator;

/**
 * @internal
 */
final class TaggedIteratorAdapter implements FilterLoaderInterface
{
    public const FILTER_TAG = 'pimcore.studio_backend.listing.filter';

    public function __construct(
        #[TaggedIterator(self::FILTER_TAG)]
        private readonly iterable $taggedFilters,
    ) {
    }

    public function loadFilters(mixed $listing): Filters
    {
        return new Filters(
            array_filter(
                iterator_to_array($this->taggedFilters),
                static function (FilterInterface $filter) use ($listing) { return $filter->supports($listing); }
            ),
        );
    }
}
