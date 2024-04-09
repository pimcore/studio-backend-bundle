<?php

namespace Pimcore\Bundle\StudioApiBundle\Service\GenericData\V1;

interface DataObjectQueryProviderInterface
{
    public function createDataObjectQuery(): DataObjectQuery;
}