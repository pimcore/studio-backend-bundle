<?php

namespace Pimcore\Bundle\StudioBackendBundle\Listing\Mapper;

use Pimcore\Bundle\StudioBackendBundle\Filter\MappedParameter\FilterParameter;

interface QueryToPayloadFilterMapperInterface
{
    public function map(mixed $parameters): FilterParameter;
}