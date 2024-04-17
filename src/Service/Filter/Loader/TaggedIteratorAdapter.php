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

namespace Pimcore\Bundle\StudioApiBundle\Service\Filter\Loader;

use Pimcore\Bundle\StudioApiBundle\Service\Filter\FilterLoaderInterface;
use Pimcore\Bundle\StudioApiBundle\Service\Filter\Filters;
use Symfony\Component\DependencyInjection\Attribute\TaggedIterator;

/**
 * @internal
 */
final class TaggedIteratorAdapter implements FilterLoaderInterface
{
    public const FILTER_TAG = 'pimcore.studio_api.collection.filter';

    public const FILTER_ASSET_TAG = 'pimcore.studio_api.collection.asset.filter';
    public const FILTER_DATA_OBJECT_TAG = 'pimcore.studio_api.collection.data_object.filter';
    public const FILTER_DOCUMENT_TAG = 'pimcore.studio_api.collection.document.filter';

    public function __construct(
        #[TaggedIterator(self::FILTER_TAG)]
        private readonly iterable $taggedFilters,
        #[TaggedIterator(self::FILTER_ASSET_TAG)]
        private readonly iterable $taggedAssetFilters,
        #[TaggedIterator(self::FILTER_DATA_OBJECT_TAG)]
        private readonly iterable $taggedDataObjectFilters,
        #[TaggedIterator(self::FILTER_DOCUMENT_TAG)]
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
