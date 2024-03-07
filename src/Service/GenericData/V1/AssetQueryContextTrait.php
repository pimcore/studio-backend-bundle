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
