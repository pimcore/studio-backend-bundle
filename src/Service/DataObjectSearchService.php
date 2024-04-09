<?php

namespace Pimcore\Bundle\StudioApiBundle\Service;

use Pimcore\Bundle\StudioApiBundle\Service\GenericData\DataObjectSearchAdapterInterface;
use Pimcore\Bundle\StudioApiBundle\Service\GenericData\V1\DataObjectQuery;
use Pimcore\Model\DataObject\Concrete;

final readonly class DataObjectSearchService implements DataObjectSearchServiceInterface
{
    public function __construct(private DataObjectSearchAdapterInterface $dataObjectSearchAdapter)
    {
    }

    public function searchDataObjects(DataObjectQuery $dataObjectQuery): DataObjectSearchResult
    {
        return $this->dataObjectSearchAdapter->searchDataObjects($dataObjectQuery);
    }

    public function getDataObjectById(int $id): Concrete|null
    {
        return null;
    }
}