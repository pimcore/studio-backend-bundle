<?php

namespace Pimcore\Bundle\StudioApiBundle\Service\GenericData\V1;

use Pimcore\Bundle\GenericDataIndexBundle\Service\Search\SearchService\DataObject\DataObjectSearchServiceInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\Element\ServiceResolver;
use Pimcore\Bundle\StudioApiBundle\Service\DataObjectSearchResult;
use Pimcore\Bundle\StudioApiBundle\Service\GenericData\DataObjectSearchAdapterInterface;
use Pimcore\Model\Element\ElementInterface;

final readonly class DataObjectSearchAdapter implements DataObjectSearchAdapterInterface
{
    public function __construct(
        private DataObjectSearchServiceInterface $searchService,
        private ServiceResolver $serviceResolver
    ) {
    }

    public function searchDataObjects(DataObjectQuery $dataObjectQuery): DataObjectSearchResult
    {
        $searchResult = $this->searchService->search($dataObjectQuery->getSearch());
        $result = array_map(
            fn(int $id) => $this->serviceResolver->getElementById('object', $id),
            $searchResult->getIds()
        );

        return new DataObjectSearchResult(
            $result,
            $searchResult->getPagination()->getPage(),
            $searchResult->getPagination()->getPageSize(),
            $searchResult->getPagination()->getTotalItems(),
        );
    }

    public function getDataObjectById(int $id): ?ElementInterface
    {
        return $this->serviceResolver->getElementById('object', $id);
    }
}