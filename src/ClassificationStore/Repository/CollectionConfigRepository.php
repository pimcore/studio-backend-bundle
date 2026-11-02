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
use function array_values;

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
        $list->setCondition('storeId = :scopeStoreId', ['scopeStoreId' => $storeId]);

        if ($collectionIds !== null) {
            $this->applyCollectionIdsFilter($list, $collectionIds);
        }

        $this->searchHelperService->applySearchTermFilter($list, $searchTerm);

        return $list->load();
    }

    public function getCountByStoreId(int $storeId): int
    {
        $list = new Listing();
        $list->setCondition('storeId = :scopeStoreId', ['scopeStoreId' => $storeId]);

        return $list->count();
    }

    /**
     * Named, like the store restriction and the search condition this shares its statement with:
     * DBAL cannot bind named and positional placeholders in one query.
     */
    private function applyCollectionIdsFilter(Listing $list, array $collectionIds): void
    {
        $placeholders = [];
        $parameters = [];

        foreach (array_values($collectionIds) as $index => $collectionId) {
            $parameter = 'scopeCollectionId_' . $index;
            $placeholders[] = ':' . $parameter;
            $parameters[$parameter] = $collectionId;
        }

        $list->addConditionParam('id IN ('. implode(',', $placeholders) .')', $parameters);
    }

    private function getOffset(CollectionParametersInterface $collectionParameters): int
    {
        return ($collectionParameters->getPage() - 1) * $collectionParameters->getPageSize();
    }
}
