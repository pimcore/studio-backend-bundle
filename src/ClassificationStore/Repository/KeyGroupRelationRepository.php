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
use Pimcore\Model\DataObject\Classificationstore\KeyGroupRelation\Listing;

/**
 * @internal
 */
final class KeyGroupRelationRepository implements KeyGroupRelationRepositoryInterface
{

    public function __construct(
        private GroupConfigRepositoryInterface $groupConfigRepository
    )
    {
    }

    /**
     * {@inheritdoc}
     */
    public function getPaginatedKeyGroupRelationByStore(
        int $storeId,
        CollectionParametersInterface $collectionParameters,
        ?array $groupIds = null
    ): array {

        $groupIds = array_map(
            fn($group) => $group->getId(),
            $this->groupConfigRepository->getAllGroupsByStore($storeId, $groupIds)
        );

        $listing = new Listing();
        $listing->setOffset($this->getOffset($collectionParameters));
        $listing->setOrder('ASC');
        $listing->setOrderKey('sorter');
        $this->applyGroupIdsFilter($listing, $groupIds);

        return $listing->load();
    }

    public function getCountByStoreId(int $storeId, ?array $groupIds = null): int
    {
        $groupIds = array_map(
            fn($group) => $group->getId(),
            $this->groupConfigRepository->getAllGroupsByStore($storeId, $groupIds)
        );

        $listing = new Listing();
        $this->applyGroupIdsFilter($listing, $groupIds);

        return $listing->count();
    }


    private function applyGroupIdsFilter(Listing $list, array $groupIds): void
    {
        $placeholders = implode(',', array_fill(0, count($groupIds), '?'));
        $list->addConditionParam('groupID IN ('. $placeholders .')', $groupIds);
    }

    private function getOffset(CollectionParametersInterface $collectionParameters): int
    {
        return ($collectionParameters->getPage() - 1) * $collectionParameters->getPageSize();
    }
}