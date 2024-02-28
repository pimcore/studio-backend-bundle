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

namespace Pimcore\Bundle\StudioApiBundle\Service\GenericData;

use Pimcore\Bundle\StudioApiBundle\Dto\Asset;
use Pimcore\Bundle\StudioApiBundle\Service\AssetSearchResult;

interface AssetSearchAdapterInterface
{
    /**
     * @param int $page
     * @param int $pageSize
     * @param string|null $searchTerm
     * @param int|null $parentId
     *
     * @return AssetSearchResult
     */
    public function searchAsset(
        int $page,
        int $pageSize,
        ?string $searchTerm,
        ?int $parentId = null
    ): AssetSearchResult;

    public function getAssetById(int $id): ?Asset;
}
