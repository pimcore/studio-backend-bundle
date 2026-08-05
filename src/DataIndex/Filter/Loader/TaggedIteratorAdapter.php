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

namespace Pimcore\Bundle\StudioBackendBundle\DataIndex\Filter\Loader;

use Pimcore\Bundle\StudioBackendBundle\DataIndex\Filter\FilterLoaderInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Filter\Filters;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

/**
 * @internal
 */
final class TaggedIteratorAdapter implements FilterLoaderInterface
{
    public const FILTER_TAG = 'pimcore.studio_backend.search_index.filter';

    public const FILTER_ASSET_TAG = 'pimcore.studio_backend.search_index.asset.filter';

    public const FILTER_DATA_OBJECT_TAG = 'pimcore.studio_backend.search_index.data_object.filter';

    public const FILTER_DOCUMENT_TAG = 'pimcore.studio_backend.search_index.document.filter';

    public function __construct(
        #[AutowireIterator(self::FILTER_TAG)]
        private readonly iterable $taggedFilters,
        #[AutowireIterator(self::FILTER_ASSET_TAG)]
        private readonly iterable $taggedAssetFilters,
        #[AutowireIterator(self::FILTER_DATA_OBJECT_TAG)]
        private readonly iterable $taggedDataObjectFilters,
        #[AutowireIterator(self::FILTER_DOCUMENT_TAG)]
        private readonly iterable $taggedDocumentFilters
    ) {
    }

    public function loadFilters(): Filters
    {
        return new Filters(
            [... $this->taggedFilters],
            [... $this->taggedAssetFilters],
            [... $this->taggedDataObjectFilters],
            [... $this->taggedDocumentFilters]
        );
    }
}
