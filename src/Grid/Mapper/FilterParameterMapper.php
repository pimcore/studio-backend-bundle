<?php
declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
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
                direction: $filters['sortFilter']['direction'] ?? SortDirection::ASC->value
            )
        );
    }
}
