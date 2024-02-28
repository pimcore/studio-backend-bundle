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

namespace Pimcore\Bundle\StudioApiBundle\Service\GenericData\V1\Hydrator\Asset;

use Pimcore\Bundle\GenericDataIndexBundle\Model\Search\Asset\AssetSearchResult\AssetMetaData;
use Pimcore\Bundle\StudioApiBundle\Dto\Asset\MetaData;

interface MetaDataHydratorInterface
{
    /**
     * @param array<int, AssetMetaData> $metaData
     *
     * @return array<int, MetaData>
     */
    public function hydrate(array $metaData): array;
}
