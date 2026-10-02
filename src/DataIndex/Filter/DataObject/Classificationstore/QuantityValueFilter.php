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

    private const VALUE_FIELD_SUFFIX = '.value';

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

            $valueFilter = $this->createValueFilter($key->getName(), $value['setting'], $value);
            if ($valueFilter !== null) {
                $query->classificationStoreFilter(
                    $column->getKeyWithOutLocale(),
                    $group->getName(),
                    $valueFilter,
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

    /**
     * @throws InvalidArgumentException
     */
    private function createValueFilter(
        string $keyName,
        string $setting,
        array $value
    ): NumberFilter|NumberRangeFilter|null
    {
        $field = $keyName . self::VALUE_FIELD_SUFFIX;

        return match (true) {
            $setting === 'is' && isset($value['is']) => new NumberFilter($field, $value['is'], true),
            $setting === 'less' && isset($value['to']) => new NumberRangeFilter($field, null, $value['to'], true),
            $setting === 'more' && isset($value['from']) => new NumberRangeFilter($field, $value['from'], null, true),
            $setting === 'between' => $this->createBetweenFilter($field, $value),
            default => null,
        };
    }

    /**
     * @throws InvalidArgumentException
     */
    private function createBetweenFilter(string $field, array $value): NumberRangeFilter
    {
        if (!isset($value['from'], $value['to'])) {
            throw new InvalidArgumentException('Between filter requires from and to');
        }

        return new NumberRangeFilter($field, $value['from'], $value['to'], true);
    }
}
