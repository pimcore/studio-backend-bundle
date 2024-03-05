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

namespace Pimcore\Bundle\StudioApiBundle\Service\GenericData\V1\Hydrator\Asset;

use Pimcore\Bundle\GenericDataIndexBundle\Model\Search\Asset\SearchResult\AssetMetaData;
use Pimcore\Bundle\StudioApiBundle\Dto\Asset\MetaData;

final class MetaDataHydrator implements MetaDataHydratorInterface
{
    /**
     * @param array<int, AssetMetaData> $metaData
     *
     * @return array<int, MetaData>
     */
    public function hydrate(array $metaData): array
    {
        $result = [];
        foreach ($metaData as $item) {
            $result[] = new MetaData(
                $item->getName(),
                $item->getLanguage(),
                $item->getType(),
                $item->getData()
            );
        }

        return $result;
    }
}
