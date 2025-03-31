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


namespace Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Repository;

use Pimcore\Bundle\StudioBackendBundle\MappedParameter\CollectionParametersInterface;

/**
 * @internal
 */
interface CollectionConfigRepositoryInterface
{
    /**
     * @param CollectionParametersInterface $collectionParameters
     * @param array<int, int>|null $collectionIds
     * @return array
     */
    public function getPaginatedCollectionsByStore(
        int $storeId,
        CollectionParametersInterface $collectionParameters,
        array $collectionIds = null
    ): array;

    public function getCountByStoreId(int $storeId,): int;

}