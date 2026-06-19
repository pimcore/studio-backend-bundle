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
use Pimcore\Model\DataObject\Classificationstore\CollectionConfig\Listing;
use function count;

/**
 * @internal
 */
final class CollectionConfigRepository implements CollectionConfigRepositoryInterface
{
    public function __construct(
        private SearchHelperServiceInterface $searchHelperService
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function getPaginatedCollectionsByStore(
        int $storeId,
        CollectionParametersInterface $collectionParameters,
        ?array $collectionIds = null,
        ?string $searchTerm = null
    ): array {
        $list = new Listing();

        $list->setLimit($collectionParameters->getPageSize());
        $list->setOffset($this->getOffset($collectionParameters));
        $list->setOrder('ASC');
        $list->setOrderKey('id');
        $list->setCondition('storeId = ?', $storeId);

        if ($collectionIds !== null) {
            $this->applyCollectionIdsFilter($list, $collectionIds);
        }

        $this->searchHelperService->applySearchTermFilter($list, $searchTerm);

        return $list->load();
    }

    public function getCountByStoreId(int $storeId): int
    {
        $list = new Listing();
        $list->setCondition('storeId = ?', $storeId);

        return $list->count();
    }

    private function applyCollectionIdsFilter(Listing $list, array $collectionIds): void
    {
        $placeholders = implode(',', array_fill(0, count($collectionIds), '?'));
        $list->addConditionParam('id IN ('. $placeholders .')', $collectionIds);
    }

    private function getOffset(CollectionParametersInterface $collectionParameters): int
    {
        return ($collectionParameters->getPage() - 1) * $collectionParameters->getPageSize();
    }
}
