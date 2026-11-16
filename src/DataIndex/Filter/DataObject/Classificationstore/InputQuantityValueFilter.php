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

use Pimcore\Bundle\GenericDataIndexBundle\Model\Search\Modifier\FullTextSearch\WildcardSearch;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Filter\FilterInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Query\DataObjectQueryInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Query\QueryInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Service\ClassificationStoreFilterValueResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Service\ClassificationStoreSegmentResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Grid\Column\ColumnType;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\Filter\ColumnFiltersParameterInterface;

/**
 * @internal
 */
final class InputQuantityValueFilter implements FilterInterface
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

        foreach (
            $parameters->getColumnFilterByType(ColumnType::CLASSIFICATION_STORE_INPUT_QUANTITY_VALUE->value) as $column
        ) {

            $filterValue = $this->filterValueResolver->resolve(
                $column->getFilterValue(),
                $parameters,
                $column->getKeyWithOutLocale()
            );

            $group = $this->segmentResolver->resolveGroupSegment($filterValue);
            $fieldName = $this->segmentResolver->resolveFieldName($filterValue);
            $locale = $this->segmentResolver->resolveLocale($filterValue, $column, $parameters);
            $value = $filterValue->getValue();

            if (!isset($value['value'], $value['unitId'])) {
                throw new InvalidArgumentException('Value must contain valu and unitId');
            }

            $query->classificationStoreFilter(
                $column->getKeyWithOutLocale(),
                $group,
                new WildcardSearch($fieldName . '.value', $value['value'], true),
                $locale
            );

            $query->classificationStoreFilter(
                $column->getKeyWithOutLocale(),
                $group,
                new WildcardSearch($fieldName . '.unitId', $value['unitId'], true),
                $locale
            );
        }

        return $query;
    }
}
