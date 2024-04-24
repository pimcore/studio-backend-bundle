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

namespace Pimcore\Bundle\StudioApiBundle\OpenSearch;

use Pimcore\Bundle\StudioApiBundle\Response\Asset;
use Pimcore\Bundle\StudioApiBundle\Response\Asset\Archive;
use Pimcore\Bundle\StudioApiBundle\Response\Asset\Audio;
use Pimcore\Bundle\StudioApiBundle\Response\Asset\Document;
use Pimcore\Bundle\StudioApiBundle\Response\Asset\Folder;
use Pimcore\Bundle\StudioApiBundle\Response\Asset\Image;
use Pimcore\Bundle\StudioApiBundle\Response\Asset\Text;
use Pimcore\Bundle\StudioApiBundle\Response\Asset\Unknown;
use Pimcore\Bundle\StudioApiBundle\Response\Asset\Video;

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
     * @return array<int, Asset|Archive|Audio|Document|Folder|Image|Text|Unknown|Video>
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
