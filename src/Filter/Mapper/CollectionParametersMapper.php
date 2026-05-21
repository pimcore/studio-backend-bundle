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

namespace Pimcore\Bundle\StudioBackendBundle\Filter\Mapper;

use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Filter\FilterType;
use Pimcore\Bundle\StudioBackendBundle\Filter\MappedParameter\FilterParameter;
use Pimcore\Bundle\StudioBackendBundle\Listing\Mapper\FilterMapperInterface;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\CollectionFilterParameter;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\CollectionParameters;

final class CollectionParametersMapper implements FilterMapperInterface
{
    /**
     * @throws InvalidArgumentException
     */
    public function map(mixed $parameters): FilterParameter
    {
        if (!$parameters instanceof CollectionParameters && !$parameters instanceof CollectionFilterParameter) {
            return new FilterParameter();
        }

        $filters = $parameters->getFilters();
        if ($filters === null) {
            return new FilterParameter();
        }

        $columnFilters = $filters->getColumnFilters();
        $columnFilters = $this->addPaging($parameters, $filters, $columnFilters);

        return new FilterParameter(
            columnFilters: $columnFilters,
            sortFilter: $filters->getSortFilter(),
            additionalSortFilters: $filters->getAdditionalSortFilters(),
        );
    }

    private function addPaging(
        CollectionParameters|CollectionFilterParameter $parameters,
        FilterParameter $filters,
        array $columnFilters
    ): array {
        if ($parameters instanceof CollectionFilterParameter) {
            return $this->addPagingFromFilters($filters, $columnFilters);
        }

        $columnFilters[] = $this->addPageColumn($parameters->getPage());
        $columnFilters[] = $this->addPageSizeColumn($parameters->getPageSize());

        return $columnFilters;
    }

    private function addPagingFromFilters(FilterParameter $filters, array $columnFilters): array
    {
        $columnFilters[] = $this->addPageColumn($filters->getPage());
        $columnFilters[] = $this->addPageSizeColumn($filters->getPageSize());

        return $columnFilters;
    }

    private function addPageColumn(int $page): array
    {
        return [
            'key' => 'page',
            'type' => FilterType::PAGE->value,
            'filterValue' => $page,
        ];
    }

    public function addPageSizeColumn(int $pageSize): array
    {
        return [
            'key' => 'pageSize',
            'type' => FilterType::PAGE_SIZE->value,
            'filterValue' => $pageSize,
        ];
    }
}
