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
use Pimcore\Bundle\StudioApiBundle\Service\GenericData\AssetSearchAdapterInterface;

final readonly class AssetSearchService implements AssetSearchServiceInterface
{
    public function __construct(private AssetSearchAdapterInterface $assetSearchAdapter)
    {
    }

    public function searchAssets(int $page = 1, int $pageSize = 50, ?string $query = null, ?int $parentId = null): AssetSearchResult
    {
        return $this->assetSearchAdapter->searchAsset($page, $pageSize, $query, $parentId);
    }

    public function getAssetById(int $id): ?Asset
    {
        return $this->assetSearchAdapter->getAssetById($id);
    }
}
