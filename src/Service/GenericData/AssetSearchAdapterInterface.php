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
use Pimcore\Bundle\StudioApiBundle\Service\GenericData\V1\AssetQuery;

interface AssetSearchAdapterInterface
{
    public function searchAssets(AssetQuery $assetQuery): AssetSearchResult;

    public function getAssetById(int $id): ?Asset;
}
