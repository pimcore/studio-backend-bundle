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

namespace Pimcore\Bundle\StudioBackendBundle\DataIndex\Filter\DataObject\Classificationstore;

use Pimcore\Bundle\StudioBackendBundle\DataIndex\Filter\DateTimeTrait;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Filter\FilterInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Query\DataObjectQueryInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Query\QueryInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Service\ClassificationStoreFilterValueResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Service\ClassificationStoreSegmentResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Grid\Column\ColumnType;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\Filter\ColumnFiltersParameterInterface;

/**
 * @internal
 */
final class DateFilter implements FilterInterface
{
    use DateTimeTrait;

    public function __construct(
        private readonly ClassificationStoreFilterValueResolverInterface $filterValueResolver,
        private readonly ClassificationStoreSegmentResolverInterface $segmentResolver
    ) {

    }

    public function apply(mixed $parameters, QueryInterface $query): QueryInterface
    {
        if (!$parameters instanceof ColumnFiltersParameterInterface) {
            return $query;
        }

        if (!$query instanceof DataObjectQueryInterface) {
            return $query;
        }

        foreach ($parameters->getColumnFilterByType(ColumnType::CLASSIFICATION_STORE_DATE->value) as $column) {
            $filterValue = $this->filterValueResolver->resolve(
                $column->getFilterValue(),
                $parameters,
                $column->getKeyWithOutLocale()
            );
            /** @var DataObjectQueryInterface $query */
            $query = $this->applyClassificationStoreDateFilter(
                $column,
                $query,
                $this->segmentResolver->resolveGroupSegment($filterValue),
                $this->segmentResolver->resolveFieldName($filterValue),
                $this->segmentResolver->resolveLocale($filterValue, $column, $parameters),
                $filterValue,
                true
            );
        }

        return $query;
    }
}
