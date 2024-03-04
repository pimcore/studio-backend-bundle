<?php
declare(strict_types=1);

namespace Pimcore\Bundle\StudioApiBundle\Service\GenericData\V1;

trait AssetQueryContextTrait
{
    private readonly AssetQueryProviderInterface $assetQueryProvider;

    private function setAssetQuery(array &$context, AssetQuery $assetQuery): void
    {
        $context[AssetQuery::ASSET_QUERY_ID] = $assetQuery;
    }

    private function getAssetQuery(array $context): AssetQuery
    {
        if (!array_key_exists(AssetQuery::ASSET_QUERY_ID, $context)) {
            return $this->assetQueryProvider->createAssetQuery();
        }

        return $context[AssetQuery::ASSET_QUERY_ID];
    }
}