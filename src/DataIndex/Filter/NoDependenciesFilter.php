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

namespace Pimcore\Bundle\StudioBackendBundle\DataIndex\Filter;

use Pimcore\Bundle\StudioBackendBundle\DataIndex\Query\QueryInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Grid\Column\ColumnType;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\Filter\ColumnFiltersParameterInterface;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\Filter\TagFilterParameterInterface;

/**
 * @internal
 */
final class NoDependenciesFilter implements FilterInterface
{
    public function apply(mixed $parameters, QueryInterface $query): QueryInterface
    {
        if (!$parameters instanceof ColumnFiltersParameterInterface) {
            return $query;
        }

        $filter = $parameters->getFirstColumnFilterByType(ColumnType::SYSTEM_UNREFERENCED_DEPENDENCIES->value);

        if (!$filter) {
            return $query;
        }

        if (!is_bool($filter->getFilterValue())) {
            throw new InvalidArgumentException('Filter value for unreferenced dependencies must be a boolean');
        }

        if ($filter->getFilterValue()) {
            $query = $query->filterNoDependencies();
        }

        return $query;
    }
}
