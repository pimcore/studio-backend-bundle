<?php

namespace Pimcore\Bundle\StudioBackendBundle\Listing\Mapper;

use Pimcore\Bundle\StudioBackendBundle\Filter\MappedParameter\FilterParameter;
use Pimcore\Bundle\StudioBackendBundle\Grid\Column\ColumnType;
use Pimcore\Bundle\StudioBackendBundle\Property\MappedParameter\PropertiesParameters;

final readonly class QueryToPayloadFilterMapper implements QueryToPayloadFilterMapperInterface
{

    public function map(mixed $parameters): FilterParameter
    {
        return $this->matchParameters($parameters);
    }


    private function matchParameters(mixed $parameters): FilterParameter
    {
       return match(true) {
           $parameters instanceof PropertiesParameters => $this->mapPropertiesParameters($parameters),
       };
    }

    private function mapPropertiesParameters(PropertiesParameters $parameters): FilterParameter
    {

        return new FilterParameter(
            columnFilters: [
                [
                    'key' => 'properties',
                    'type' => ColumnType::PROPERTY_NAME->value,
                    'filterValue' => $parameters->getFilter(),
                ],
                [
                    'key' => 'properties',
                    'type' => ColumnType::PROPERTY_ELEMENT_TYPE->value,
                    'filterValue' => $parameters->getElementType(),
                ]
            ]
        );
    }
}