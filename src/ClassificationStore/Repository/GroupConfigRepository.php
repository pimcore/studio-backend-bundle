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
use Pimcore\Model\DataObject\Classificationstore\GroupConfig;
use Pimcore\Model\DataObject\Classificationstore\GroupConfig\Listing;
use function array_values;

/**
 * @internal
 */
final class GroupConfigRepository implements GroupConfigRepositoryInterface
{
    /**
     * `scopeStoreId`, not `storeId`: a client supplied equals filter on `storeId` binds its own
     * parameter, so the route scope keeps a name of its own that such a filter cannot overwrite.
     */
    private const string STORE_SCOPE_PARAMETER = 'scopeStoreId';

    private const string STORE_SCOPE_CONDITION = 'storeId = :' . self::STORE_SCOPE_PARAMETER;

    public function __construct(
        private SearchHelperServiceInterface $searchHelperService
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function getPaginatedGroupsByStore(
        int $storeId,
        CollectionParametersInterface $collectionParameters,
        ?array $groupIds = null,
        ?string $searchTerm = null
    ): array {
        $listing = new Listing();

        $listing->setLimit($collectionParameters->getPageSize());
        $listing->setOffset($this->getOffset($collectionParameters));
        $listing->setOrder('ASC');
        $listing->setOrderKey('id');
        $listing->setCondition(self::STORE_SCOPE_CONDITION, [self::STORE_SCOPE_PARAMETER => $storeId]);

        $this->searchHelperService->applySearchTermFilter($listing, $searchTerm);

        if ($groupIds !== null) {
            $this->applyGroupIdsFilter($listing, $groupIds);
        }

        return $listing->load();
    }

    public function getAllGroupsByStore(int $storeId, ?array $groupIds = null): array
    {
        $listing = new Listing();

        $listing->setCondition(self::STORE_SCOPE_CONDITION, [self::STORE_SCOPE_PARAMETER => $storeId]);

        if ($groupIds !== null) {
            $this->applyGroupIdsFilter($listing, $groupIds);
        }

        return $listing->load();
    }

    public function getCountByStoreId(int $storeId): int
    {
        $listing = new Listing();
        $listing->setCondition(self::STORE_SCOPE_CONDITION, [self::STORE_SCOPE_PARAMETER => $storeId]);

        return $listing->count();
    }

    /**
     * {@inheritdoc}
     */
    public function getById(int $id): GroupConfig
    {
        $group = GroupConfig::getById($id);

        if (!$group) {
            throw new NotFoundException('group', $id);
        }

        return $group;
    }

    /**
     * Named, like the store restriction and the search condition this shares its statement with:
     * DBAL cannot bind named and positional placeholders in one query.
     */
    private function applyGroupIdsFilter(Listing $list, array $groupIds): void
    {
        $placeholders = [];
        $parameters = [];

        foreach (array_values($groupIds) as $index => $groupId) {
            $parameter = 'scopeGroupId_' . $index;
            $placeholders[] = ':' . $parameter;
            $parameters[$parameter] = $groupId;
        }

        $list->addConditionParam('id IN ('. implode(',', $placeholders) .')', $parameters);
    }

    private function getOffset(CollectionParametersInterface $collectionParameters): int
    {
        return ($collectionParameters->getPage() - 1) * $collectionParameters->getPageSize();
    }
}
