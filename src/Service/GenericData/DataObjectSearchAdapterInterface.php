<?php

namespace Pimcore\Bundle\StudioApiBundle\Service\GenericData;

use Pimcore\Bundle\StudioApiBundle\Service\DataObjectSearchResult;
use Pimcore\Bundle\StudioApiBundle\Service\GenericData\V1\DataObjectQuery;
use Pimcore\Model\Element\ElementInterface;

interface DataObjectSearchAdapterInterface
{
    public function searchDataObjects(DataObjectQuery $dataObjectQuery): DataObjectSearchResult;

    public function getDataObjectById(int $id): ?ElementInterface;
}