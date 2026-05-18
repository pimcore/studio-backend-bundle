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

use Pimcore\Bundle\GenericDataIndexBundle\Model\Search\Modifier\Filter\Basic\NumberFilter;
use Pimcore\Bundle\GenericDataIndexBundle\Model\Search\Modifier\Filter\FieldType\NumberRangeFilter;
use Pimcore\Bundle\GenericDataIndexBundle\Model\Search\Modifier\FullTextSearch\WildcardSearch;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Repository\GroupConfigRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Repository\KeyGroupRelationRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Filter\FilterInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Query\DataObjectQueryInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Query\QueryInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Utils\GetClassificationStoreFilterValueTrait;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Grid\Column\ColumnType;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\Filter\ColumnFiltersParameterInterface;

/**
 * @internal
 */
final class QuantityValueFilter implements FilterInterface
{
    use GetClassificationStoreFilterValueTrait;

    public function __construct(
        private readonly GroupConfigRepositoryInterface $groupConfigRepository,
        private readonly KeyGroupRelationRepositoryInterface $keyGroupRelationRepository
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
            $parameters->getColumnFilterByType(ColumnType::CLASSIFICATION_STORE_QUANTITY_VALUE->value) as $column
        ) {

            $filterValue = $this->getClassificationStoreFilterValue($column->getFilterValue());

            $key = $this->keyGroupRelationRepository->getByKeyGroupId(
                $filterValue->getKeyId(),
                $filterValue->getGroupId()
            );
            $group = $this->groupConfigRepository->getById($filterValue->getGroupId());
            $value = $filterValue->getValue();

            if (!isset($value['unitId'])) {
                throw new InvalidArgumentException('Value must contain unitId');
            }

            if (!isset($value['setting'])) {
                throw new InvalidArgumentException('This filter requires a setting value');
            }

            $setting = $value['setting'];

            if (isset($value['is']) && $setting == 'is') {
                $query->classificationStoreFilter(
                    $column->getKeyWithOutLocale(),
                    $group->getName(),
                    new NumberFilter($key->getName(). '.value', $value['is'], true),
                    null
                );
            }

            if (isset($value['to']) && $setting == 'less') {
                $query->classificationStoreFilter(
                    $column->getKeyWithOutLocale(),
                    $group->getName(),
                    new NumberRangeFilter($column->getKey().'value', null, $value['to'], true),
                    null
                );
            }

            if (isset($value['from']) && $setting == 'more') {
                $query->classificationStoreFilter(
                    $column->getKeyWithOutLocale(),
                    $group->getName(),
                    new NumberRangeFilter($column->getKey().'value', $value['from'], null, true),
                    null
                );
            }

            if ($setting == 'between') {
                $query->classificationStoreFilter(
                    $column->getKeyWithOutLocale(),
                    $group->getName(),
                    new NumberRangeFilter($column->getKey().'value', $value['from'], $value['to'], true),
                    null
                );
            }

            $query->classificationStoreFilter(
                $column->getKeyWithOutLocale(),
                $group->getName(),
                new WildcardSearch($key->getName(). '.unitId', $value['unitId'], true),
                null
            );
        }

        return $query;
    }
}
