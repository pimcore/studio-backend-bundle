<?php
declare(strict_types=1);

namespace Pimcore\Bundle\StudioApiBundle\Service;

use Pimcore\Bundle\StudioApiBundle\Dto\Asset;

interface AssetSearchServiceInterface
{
    public function searchAssets(int $page = 1, int $pageSize = 50, ?string $query = null, ?int $parentId = null): AssetSearchResult;

    public function getAssetById(int $id): ?Asset;
}