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

use Pimcore\Bundle\GenericDataIndexBundle\Model\Search\Modifier\Filter\Basic\IntegerFilter;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Filter\FilterInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Query\DataObjectQueryInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Query\QueryInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Service\ClassificationStoreFilterValueResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Service\ClassificationStoreSegmentResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Grid\Column\ColumnType;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\Filter\ColumnFiltersParameterInterface;
use function is_float;

/**
 * @internal
 */
final class RGBAFilter implements FilterInterface
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

        foreach ($parameters->getColumnFilterByType(ColumnType::CLASSIFICATION_STORE_RGBA->value) as $column) {

            $filterValue = $this->filterValueResolver->resolve(
                $column->getFilterValue(),
                $parameters,
                $column->getKeyWithOutLocale()
            );

            $group = $this->segmentResolver->resolveGroupSegment($filterValue);
            $fieldName = $this->segmentResolver->resolveFieldName($filterValue);
            $locale = $this->segmentResolver->resolveLocale($filterValue, $column, $parameters);
            $value = $filterValue->getValue();

            if (!isset($value['r'], $value['g'], $value['b'], $value['a'])) {
                throw new InvalidArgumentException('Value must contain r,g,b,a');
            }

            if (!is_float($value['a']) || $value['a'] > 1) {
                $value['a'] = 1;
            }

            $query->classificationStoreFilter(
                $column->getKeyWithOutLocale(),
                $group,
                new IntegerFilter($fieldName . '.r', $value['r'], true),
                $locale
            );

            $query->classificationStoreFilter(
                $column->getKeyWithOutLocale(),
                $group,
                new IntegerFilter($fieldName . '.g', $value['g'], true),
                $locale
            );

            $query->classificationStoreFilter(
                $column->getKeyWithOutLocale(),
                $group,
                new IntegerFilter($fieldName . '.b', $value['b'], true),
                $locale
            );

            $query->classificationStoreFilter(
                $column->getKeyWithOutLocale(),
                $group,
                new IntegerFilter(
                    $fieldName . '.a',
                    (int)($value['a'] * 255),
                    true
                ),
                $locale
            );
        }

        return $query;
    }
}
