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
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\Filter\PqlParameterInterface;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\Filter\SimpleColumnFiltersParameterInterface;
use function is_string;

/**
 * @internal
 */
final class PqlFilter implements FilterInterface
{
    public function apply(mixed $parameters, QueryInterface $query): QueryInterface
    {
        if (!$parameters instanceof SimpleColumnFiltersParameterInterface &&
            !$parameters instanceof PqlParameterInterface
        ) {
            return $query;
        }

        $filterValue = $this->getFilterValue($parameters);
        if ($filterValue === null) {
            return $query;
        }

        return $query->filterByPql($filterValue);
    }

    /**
     * @throws InvalidArgumentException
     */
    private function getFilterValue(SimpleColumnFiltersParameterInterface|PqlParameterInterface $parameters): ?string
    {
        if ($parameters instanceof PqlParameterInterface) {
            return $parameters->getPqlQuery();
        }

        $filter = $parameters->getSimpleColumnFilterByType(ColumnType::SYSTEM_PQL_QUERY->value);
        if (!$filter) {
            return null;
        }

        $filterValue = $filter->getFilterValue();
        if (!is_string($filterValue)) {
            throw new InvalidArgumentException('Invalid PQL filter. Filter value must be a string.');
        }

        return $filterValue;
    }
}
