<?php
declare(strict_types=1);

namespace Pimcore\Bundle\StudioApiBundle\Service\GenericData\V1;

use Pimcore\Bundle\GenericDataIndexBundle\Service\Search\SearchService\SearchProviderInterface;

final readonly class AssetQueryProvider implements AssetQueryProviderInterface
{
    public function __construct(private SearchProviderInterface $searchProvider)
    {
    }

    public function createAssetQuery(): AssetQuery
    {
        return new AssetQuery($this->searchProvider->createAssetSearch());
    }
}