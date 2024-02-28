<?php
declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under following license:
 * - Pimcore Commercial License (PCL)
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     PCL
 */

namespace Pimcore\Bundle\StudioApiBundle\Service;

use Pimcore\Bundle\StudioApiBundle\Dto\Asset;

final readonly class AssetSearchResult
{
    /**
     * @param array<int, Asset> $items
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
     * @return array<int, Asset>
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
