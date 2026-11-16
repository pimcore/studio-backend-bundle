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

use Pimcore\Bundle\GenericDataIndexBundle\Model\Search\Modifier\Filter\FieldType\TimeFilter as GDITimeFilter;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Filter\FilterInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Query\DataObjectQueryInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Query\QueryInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Service\ClassificationStoreFilterValueResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Service\ClassificationStoreSegmentResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Utils\ClassificationStoreFilterValue;
use Pimcore\Bundle\StudioBackendBundle\Grid\Column\ColumnType;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\Filter\ColumnFilter;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\Filter\ColumnFiltersParameterInterface;

/**
 * @internal
 */
final class TimeFilter implements FilterInterface
{
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

        foreach ($parameters->getColumnFilterByType(ColumnType::CLASSIFICATION_STORE_TIME->value) as $column) {
            $filterValue = $this->filterValueResolver->resolve(
                $column->getFilterValue(),
                $parameters,
                $column->getKeyWithOutLocale()
            );
            /** @var DataObjectQueryInterface $query */
            $query = $this->applyTimeFilter(
                $column,
                $query,
                $this->segmentResolver->resolveGroupSegment($filterValue),
                $this->segmentResolver->resolveFieldName($filterValue),
                $this->segmentResolver->resolveLocale($filterValue, $column, $parameters),
                $filterValue
            );
        }

        return $query;
    }

    private function applyTimeFilter(
        ColumnFilter $column,
        DataObjectQueryInterface $query,
        string $group,
        string $fieldName,
        string $locale,
        ClassificationStoreFilterValue $filterValue,
    ): QueryInterface {

        $value = $filterValue->getValue();

        if (isset($value['from'], $value['to'])) {

            $query->classificationStoreFilter(
                $column->getKeyWithOutLocale(),
                $group,
                $this->buildTimeFilterModifier(
                    $fieldName,
                    $value['from'],
                    $value['to']
                ),
                $locale
            );

            return $query;
        }

        if (isset($value['on'])) {
            $query->classificationStoreFilter(
                $column->getKeyWithOutLocale(),
                $group,
                $this->buildTimeFilterModifier(
                    $fieldName,
                    null,
                    null,
                    $value['on']
                ),
                $locale
            );
        }

        if (isset($value['to'])) {
            $query->classificationStoreFilter(
                $column->getKeyWithOutLocale(),
                $group,
                $this->buildTimeFilterModifier(
                    $fieldName,
                    null,
                    $value['to']
                ),
                $locale
            );
        }

        if (isset($value['from'])) {
            $query->classificationStoreFilter(
                $column->getKeyWithOutLocale(),
                $group,
                $this->buildTimeFilterModifier(
                    $fieldName,
                    $value['from']
                ),
                $locale
            );
        }

        return $query;
    }

    private function buildTimeFilterModifier(
        string $field,
        ?string $startTime = null,
        ?string $endTime = null,
        ?string $onTime = null,
    ): GDITimeFilter {
        return new GDITimeFilter($field, $startTime, $endTime, $onTime);
    }
}
