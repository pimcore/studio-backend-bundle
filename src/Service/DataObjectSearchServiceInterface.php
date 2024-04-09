<?php

namespace Pimcore\Bundle\StudioApiBundle\Service;

use Pimcore\Bundle\StudioApiBundle\Service\GenericData\V1\DataObjectQuery;
use Pimcore\Model\DataObject\Concrete;

interface DataObjectSearchServiceInterface
{
    public function searchDataObjects(DataObjectQuery $dataObjectQuery): DataObjectSearchResult;

    public function getDataObjectById(int $id): Concrete|null;
}