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
use Pimcore\Model\DataObject\Classificationstore\GroupConfig;

/**
 * @internal
 */
interface GroupConfigRepositoryInterface
{
    /**
     * @param array<int, int>|null $groupIds
     *
     * @return GroupConfig[]
     */
    public function getPaginatedGroupsByStore(
        int $storeId,
        CollectionParametersInterface $collectionParameters,
        ?array $groupIds = null,
        ?string $searchTerm = null
    ): array;

    public function getAllGroupsByStore(
        int $storeId,
        ?array $groupIds = null
    ): array;

    public function getCountByStoreId(int $storeId): int;
}
