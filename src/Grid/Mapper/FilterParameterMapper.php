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

namespace Pimcore\Bundle\StudioBackendBundle\Grid\Mapper;

use Pimcore\Bundle\GenericDataIndexBundle\Enum\Search\SortDirection;
use Pimcore\Bundle\StudioBackendBundle\Filter\MappedParameter\FilterParameter;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\Filter\SortFilter;

final readonly class FilterParameterMapper implements FilterParameterMapperInterface
{
    public function fromArray(array $filters): FilterParameter
    {
        return new FilterParameter(
            page:  $filters['page'] ?? 1,
            pageSize: $filters['pageSize'] ?? 50,
            includeDescendants: $filters['pathIncludeDescendants'] ?? true,
            columnFilters: $filters['columnFilters'] ?? [],
            sortFilter: new SortFilter(
                key: $filters['sortFilter']['key'] ?? 'id',
                direction: $filters['sortFilter']['direction'] ?? SortDirection::ASC->value,
                locale: $filters['sortFilter']['locale'] ?? null
            )
        );
    }
}
