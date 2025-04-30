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

namespace Pimcore\Bundle\StudioBackendBundle\DataIndex\Filter;

use Pimcore\Bundle\GenericDataIndexBundle\Enum\Search\SortDirection;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Query\QueryInterface;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\Filter\SortFilterParameterInterface;

/**
 * @internal
 */
final class SortFilter implements FilterInterface
{
    public function apply(mixed $parameters, QueryInterface $query): QueryInterface
    {
        if (!$parameters instanceof SortFilterParameterInterface) {
            return $query;
        }

        $sortFilter = $parameters->getSortFilter();

        $sortDirection = SortDirection::ASC;

        if (strtolower($sortFilter->getDirection()) === SortDirection::DESC->value) {
            $sortDirection = SortDirection::DESC;
        }

        $query->orderByField(
            $sortFilter->getKey(),
            $sortDirection
        );

        return $query;
    }
}
