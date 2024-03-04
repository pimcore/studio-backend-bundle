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

namespace Pimcore\Bundle\StudioApiBundle\Service\GenericData\V1;

use Pimcore\Bundle\GenericDataIndexBundle\Service\Search\SearchService\Asset\AssetSearchServiceInterface;
use Pimcore\Bundle\StudioApiBundle\Dto\Asset;
use Pimcore\Bundle\StudioApiBundle\Service\AssetSearchResult;
use Pimcore\Bundle\StudioApiBundle\Service\GenericData\AssetSearchAdapterInterface;
use Pimcore\Bundle\StudioApiBundle\Service\GenericData\V1\Hydrator\AssetHydratorServiceInterface;

final readonly class AssetSearchAdapter implements AssetSearchAdapterInterface
{
    public function __construct(
        private AssetSearchServiceInterface $searchService,
        private AssetHydratorServiceInterface $assetHydratorService
    ) {
    }

    public function searchAssets(AssetQuery $assetQuery): AssetSearchResult
    {
        $searchResult = $this->searchService->search($assetQuery->getSearch());
        $result = [];
        foreach ($searchResult->getItems() as $item) {
            $result[] = $this->assetHydratorService->hydrate($item);
        }

        return new AssetSearchResult(
            $result,
            $searchResult->getPagination()->getPage(),
            $searchResult->getPagination()->getPageSize(),
            $searchResult->getPagination()->getTotalItems(),
        );
    }

    public function getAssetById(int $id): ?Asset
    {
        $searchResult = $this->searchService->byId($id);
        if ($searchResult === null) {
            return null;
        }

        return $this->assetHydratorService->hydrate($searchResult);
    }
}
