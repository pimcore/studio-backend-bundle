<?php
declare(strict_types=1);

namespace Pimcore\Bundle\StudioApiBundle\Service\GenericData\V1;

interface AssetQueryProviderInterface
{
    public function createAssetQuery(): AssetQuery;
}