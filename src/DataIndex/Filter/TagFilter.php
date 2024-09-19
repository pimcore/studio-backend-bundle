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
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\Filter\SimpleColumnFiltersParameterInterface;

/**
 * @internal
 */
final class TagFilter implements FilterInterface
{
    public function apply(mixed $parameters, QueryInterface $query): QueryInterface
    {
        if (!$parameters instanceof SimpleColumnFiltersParameterInterface) {
            return $query;
        }

        $filter = $parameters->getSimpleColumnFilterByType(ColumnType::SYSTEM_TAG->value);

        if (!$filter) {
            return $query;
        }

        $filterValue = $filter->getFilterValue();

        if (!isset($filterValue['tags'], $filterValue['considerChildTags'])) {
            throw new InvalidArgumentException('Invalid tag filter');
        }

        return $query->filterTags($filterValue['tags'], $filterValue['considerChildTags']);
    }
}
