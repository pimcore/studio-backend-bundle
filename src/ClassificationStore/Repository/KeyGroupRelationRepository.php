<?php
declare(strict_types=1);

/**
 * This source file is available under the terms of the
 * Pimcore Open Core License (POCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (https://www.pimcore.com)
 *  @license    Pimcore Open Core License (POCL)
 */

namespace Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Repository;

use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\SearchHelperServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\CollectionParametersInterface;
use Pimcore\Model\DataObject\Classificationstore\KeyGroupRelation\Listing;
use function count;

/**
 * @internal
 */
final readonly class KeyGroupRelationRepository implements KeyGroupRelationRepositoryInterface
{
    public function __construct(
        private GroupConfigRepositoryInterface $groupConfigRepository,
        private SearchHelperServiceInterface $searchHelperService
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function getPaginatedKeyGroupRelationByStore(
        int $storeId,
        CollectionParametersInterface $collectionParameters,
        ?array $groupIds = null,
        ?string $searchTerm = null
    ): array {
        $listing = $this->getBaseListing($storeId, $groupIds, $searchTerm);
        $listing->setOffset($this->getOffset($collectionParameters));
        $listing->setLimit($collectionParameters->getPageSize());

        return $listing->getList();
    }

    public function getCountByStoreId(int $storeId, ?array $groupIds = null, ?string $searchTerm = null): int
    {
        return $this->getBaseListing($storeId, $groupIds, $searchTerm)->count();
    }

    /**
     * {@inheritdoc}
     */
    public function getByGroupIds(array $groupIds): array
    {
        if (count($groupIds) === 0) {
            return [];
        }

        $listing = new Listing();
        $listing->setOrder('ASC');
        $listing->setOrderKey('id');
        $this->applyGroupIdsFilter($listing, $groupIds);

        return $listing->load();
    }

    private function getBaseListing(int $storeId, ?array $groupIds = null, ?string $searchTerm = null): Listing
    {
        $groupIds = array_map(
            static fn ($group) => $group->getId(),
            $this->groupConfigRepository->getAllGroupsByStore($storeId, $groupIds)
        );

        $listing = new Listing();
        $listing->setOrder('ASC');
        $listing->setOrderKey('sorter');
        $this->applyGroupIdsFilter($listing, $groupIds);

        $this->searchHelperService->applyKeyGroupRelationSearchFilter($listing, $searchTerm);

        return $listing;
    }

    private function applyGroupIdsFilter(Listing $list, array $groupIds): void
    {
        if (empty($groupIds)) {
            return;
        }
        $placeholders = implode(',', array_fill(0, count($groupIds), '?'));
        $list->addConditionParam('groupID IN ('. $placeholders .')', $groupIds);
    }

    private function getOffset(CollectionParametersInterface $collectionParameters): int
    {
        return ($collectionParameters->getPage() - 1) * $collectionParameters->getPageSize();
    }
}
