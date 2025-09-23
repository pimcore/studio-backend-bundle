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
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\CollectionParametersInterface;
use Pimcore\Model\DataObject\Classificationstore\GroupConfig\Dao as GroupConfigDao;
use Pimcore\Model\DataObject\Classificationstore\KeyConfig\Dao as KeyConfigDao;
use Pimcore\Model\DataObject\Classificationstore\KeyGroupRelation\Listing;
use Pimcore\Model\DataObject\Classificationstore\KeyGroupRelation;
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

        $groupIds = array_map(
            fn ($group) => $group->getId(),
            $this->groupConfigRepository->getAllGroupsByStore($storeId, $groupIds)
        );

        $listing = new Listing();
        $listing->setOffset($this->getOffset($collectionParameters));
        $listing->setOrder('ASC');
        $listing->setOrderKey('sorter');
        $this->applyGroupIdsFilter($listing, $groupIds);

        if ($searchTerm !== null) {
            $this->applySearchTermFilter($listing, $searchTerm);
        }

        return $listing->getList();
    }

    public function getCountByStoreId(int $storeId, ?array $groupIds = null): int
    {
        $groupIds = array_map(
            fn ($group) => $group->getId(),
            $this->groupConfigRepository->getAllGroupsByStore($storeId, $groupIds)
        );

        $listing = new Listing();
        $this->applyGroupIdsFilter($listing, $groupIds);

        return $listing->count();
    }

    /**
     * {@inheritdoc}
     */
    public function getByGroupId(int $groupId): array
    {
        $listing = new Listing();
        $listing->setOrder('ASC');
        $listing->setOrderKey('id');
        $listing->setCondition('groupID = ?', [$groupId]);

        return $listing->load();
    }

    private function applySearchTermFilter(Listing $list, string $searchTerm): void
    {
        $searchTerms = $this->searchHelperService->getTranslatedSearchFilterTerms($searchTerm);
        $searchFilterConditions = [];

        foreach ($searchTerms as $term) {
            $searchFilterConditions[] =
                KeyConfigDao::TABLE_NAME_KEYS.'.name LIKE '.$list->quote('%'.$term.'%')
                .' OR '.GroupConfigDao::TABLE_NAME_GROUPS.'.name LIKE '.$list->quote('%'.$term.'%')
                .' OR '.KeyConfigDao::TABLE_NAME_KEYS.'.description LIKE '.$list->quote('%'.$term.'%');
        }
        $list->setResolveGroupName(true);

        $list->addConditionParam(implode(' OR ', $searchFilterConditions));
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

    /**
     * @inheritDoc
     */
    public function getByKeyId(int $keyId): KeyGroupRelation {

        $listing = new Listing();
        $listing->setOrder('ASC');
        $listing->setCondition('id = ?', $keyId);

        $list = $listing->load();

        if(count($list) != 1) {
            throw new NotFoundException('KeyGroupRelation', $keyId);
        }

        return $list[0];
    }
}
