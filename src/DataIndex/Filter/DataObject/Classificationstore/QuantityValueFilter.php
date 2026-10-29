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
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Filter\FilterModes;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Query\DataObjectQueryInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Query\QueryInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Utils\GetClassificationStoreFilterValueTrait;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Grid\Column\ColumnType;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\Filter\ColumnFiltersParameterInterface;
use function implode;
use function is_numeric;
use function is_string;

/**
 * @internal
 */
final class QuantityValueFilter implements FilterInterface
{
    use GetClassificationStoreFilterValueTrait;

    private const VALUE_FIELD_SUFFIX = '.value';

    private const UNIT_FIELD_SUFFIX = '.unitId';

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

            $mode = $this->getMode($value['setting'] ?? null);

            $query->classificationStoreFilter(
                $column->getKeyWithOutLocale(),
                $group->getName(),
                $this->createValueFilter($mode, $key->getName() . self::VALUE_FIELD_SUFFIX, $value),
                null
            );

            $query->classificationStoreFilter(
                $column->getKeyWithOutLocale(),
                $group->getName(),
                new WildcardSearch($key->getName() . self::UNIT_FIELD_SUFFIX, $value['unitId'], true),
                null
            );
        }

        return $query;
    }

    /**
     * @throws InvalidArgumentException
     */
    private function getMode(mixed $setting): FilterModes
    {
        $mode = is_string($setting) ? FilterModes::tryFrom($setting) : null;
        if ($mode === null) {
            throw new InvalidArgumentException('Setting must be one of: ' . implode(', ', FilterModes::values()));
        }

        return $mode;
    }

    /**
     * @throws InvalidArgumentException
     */
    private function createValueFilter(FilterModes $mode, string $field, array $value): NumberFilter|NumberRangeFilter
    {
        return match ($mode) {
            FilterModes::IS => new NumberFilter($field, $this->toNumber($value['is'] ?? null), true),
            FilterModes::LESS => new NumberRangeFilter($field, null, $this->toNumber($value['to'] ?? null), true),
            FilterModes::MORE => new NumberRangeFilter($field, $this->toNumber($value['from'] ?? null), null, true),
            FilterModes::BETWEEN => new NumberRangeFilter(
                $field,
                $this->toNumber($value['from'] ?? null),
                $this->toNumber($value['to'] ?? null),
                true
            ),
        };
    }

    /**
     * @throws InvalidArgumentException
     */
    private function toNumber(mixed $value): int|float
    {
        if (!is_numeric($value)) {
            throw new InvalidArgumentException('Filter values must be numeric.');
        }

        return $value + 0;
    }
}
