<?php

namespace Pimcore\Bundle\StudioApiBundle\Filter;

use Pimcore\Bundle\StudioApiBundle\Dto\Collection;
use Pimcore\Bundle\StudioApiBundle\Service\GenericData\V1\QueryInterface;

interface FilterInterface
{
    public function apply(Collection $collection, QueryInterface $query): mixed;
}