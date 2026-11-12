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
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Repository\GroupConfigRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\KeyRelationResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Filter\FilterInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Query\DataObjectQueryInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Query\QueryInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Service\ClassificationStoreFilterValueResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Utils\ClassificationStoreFilterValue;
use Pimcore\Bundle\StudioBackendBundle\Grid\Column\ColumnType;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\Filter\ColumnFilter;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\Filter\ColumnFiltersParameterInterface;
use Pimcore\Model\DataObject\Classificationstore\GroupConfig;
use Pimcore\Model\DataObject\Classificationstore\KeyGroupRelation;

/**
 * @internal
 */
final class TimeFilter implements FilterInterface
{
    public function __construct(
        private readonly GroupConfigRepositoryInterface $groupConfigRepository,
        private readonly KeyRelationResolverInterface $keyRelationResolver,
        private readonly ClassificationStoreFilterValueResolverInterface $filterValueResolver
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
            $key = $this->keyRelationResolver->getByGroupAndKey(
                $filterValue->getGroupId(),
                $filterValue->getKeyId()
            );
            $group = $this->groupConfigRepository->getById($filterValue->getGroupId());
            /** @var DataObjectQueryInterface $query */
            $query = $this->applyTimeFilter($column, $query, $key, $group, $filterValue);
        }

        return $query;
    }

    private function applyTimeFilter(
        ColumnFilter $column,
        DataObjectQueryInterface $query,
        KeyGroupRelation $key,
        GroupConfig $group,
        ClassificationStoreFilterValue $filterValue,
    ): QueryInterface {

        $value = $filterValue->getValue();

        if (isset($value['from'], $value['to'])) {

            $query->classificationStoreFilter(
                $column->getKeyWithOutLocale(),
                $group->getName(),
                $this->buildTimeFilterModifier(
                    $filterValue->getIndexFieldName($key->getName()),
                    $value['from'],
                    $value['to']
                ),
                $column->getLocale()
            );

            return $query;
        }

        if (isset($value['on'])) {
            $query->classificationStoreFilter(
                $column->getKeyWithOutLocale(),
                $group->getName(),
                $this->buildTimeFilterModifier(
                    $filterValue->getIndexFieldName($key->getName()),
                    null,
                    null,
                    $value['on']
                ),
                $column->getLocale()
            );
        }

        if (isset($value['to'])) {
            $query->classificationStoreFilter(
                $column->getKeyWithOutLocale(),
                $group->getName(),
                $this->buildTimeFilterModifier(
                    $filterValue->getIndexFieldName($key->getName()),
                    null,
                    $value['to']
                ),
                $column->getLocale()
            );
        }

        if (isset($value['from'])) {
            $query->classificationStoreFilter(
                $column->getKeyWithOutLocale(),
                $group->getName(),
                $this->buildTimeFilterModifier(
                    $filterValue->getIndexFieldName($key->getName()),
                    $value['from']
                ),
                $column->getLocale()
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
