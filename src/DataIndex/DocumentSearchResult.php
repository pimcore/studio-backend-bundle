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

namespace Pimcore\Bundle\StudioBackendBundle\DataIndex;

use Pimcore\Bundle\StudioBackendBundle\DataIndex\SearchResult\SearchResultItemInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\SearchResult\SearchResultPaginationInterface;
use Pimcore\Bundle\StudioBackendBundle\Document\Schema\Document;

/**
 * @internal
 */
final readonly class DocumentSearchResult implements SearchResultItemInterface, SearchResultPaginationInterface
{
    /**
     * @param array<int, Document> $items
     * @param int $currentPage
     * @param int $pageSize
     * @param int $totalItems
     */
    public function __construct(
        private array $items,
        private int $currentPage,
        private int $pageSize,
        private int $totalItems,
    ) {
    }

    public function getTotalItems(): int
    {
        return $this->totalItems;
    }

    /**
     * @return array<int, Document>
     */
    public function getItems(): array
    {
        return $this->items;
    }

    public function getCurrentPage(): int
    {
        return $this->currentPage;
    }

    public function getPageSize(): int
    {
        return $this->pageSize;
    }
}
