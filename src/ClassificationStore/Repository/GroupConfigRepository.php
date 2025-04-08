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

use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\SearchHelperServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\CollectionParametersInterface;
use Pimcore\Model\DataObject\Classificationstore\GroupConfig;
use Pimcore\Model\DataObject\Classificationstore\GroupConfig\Listing;
use function count;

/**
 * @internal
 */
final class GroupConfigRepository implements GroupConfigRepositoryInterface
{
    public function __construct(
        private SearchHelperServiceInterface $searchHelperService
    ) {
    }

    /**
     * {@inheritDoc}
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
        $listing->setCondition('storeId = ?', $storeId);

        if ($searchTerm !== null) {
            $this->searchHelperService->applySearchTermFilter($listing, $searchTerm);
        }

        if ($groupIds !== null) {
            $this->applyGroupIdsFilter($listing, $groupIds);
        }

        return $listing->load();
    }

    public function getAllGroupsByStore(int $storeId, ?array $groupIds = null): array
    {
        $listing = new Listing();

        $listing->setCondition('storeId = ?', $storeId);

        if ($groupIds !== null) {
            $this->applyGroupIdsFilter($listing, $groupIds);
        }

        return $listing->load();
    }

    public function getCountByStoreId(int $storeId): int
    {
        $listing = new Listing();
        $listing->setCondition('storeId = ?', $storeId);

        return $listing->count();
    }

    /**
     * {@inheritDoc}
     */
    public function getById(int $id): GroupConfig
    {
        $group = GroupConfig::getById($id);

        if (!$group) {
            throw new NotFoundException('group', $id);
        }

        return $group;
    }


    private function applyGroupIdsFilter(Listing $list, array $groupIds): void
    {
        $placeholders = implode(',', array_fill(0, count($groupIds), '?'));
        $list->addConditionParam('id IN ('. $placeholders .')', $groupIds);
    }

    private function getOffset(CollectionParametersInterface $collectionParameters): int
    {
        return ($collectionParameters->getPage() - 1) * $collectionParameters->getPageSize();
    }
}
