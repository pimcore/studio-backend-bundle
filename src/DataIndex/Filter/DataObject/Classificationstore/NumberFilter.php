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
use Pimcore\Bundle\GenericDataIndexBundle\Model\Search\Modifier\Filter\FieldType\NumberRangeFilter;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Repository\GroupConfigRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Repository\KeyGroupRelationRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Filter\FilterInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Filter\FilterModes;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Query\DataObjectQueryInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Query\QueryInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Utils\GetClassificationStoreFilterValueTrait;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Grid\Column\ColumnType;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\Filter\ColumnFilter;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\Filter\ColumnFiltersParameterInterface;
use Pimcore\Model\DataObject\Classificationstore\GroupConfig;
use Pimcore\Model\DataObject\Classificationstore\KeyGroupRelation;
use function in_array;

/**
 * @internal
 */
final class NumberFilter implements FilterInterface
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

        foreach ($parameters->getColumnFilterByType(ColumnType::CLASSIFICATION_STORE_NUMBER->value) as $column) {
            $filterValue = $this->getClassificationStoreFilterValue($column->getFilterValue());
            $value = $filterValue->getValue();
            $mode = $value['setting'] ?? 'is';
            $isValue = $value['is'] ?? null;
            $fromValue = $value['from'] ?? null;
            $toValue = $value['to'] ?? null;

            $this->validate($isValue, $fromValue, $toValue, $mode);
            $this->addFilterToQuery(
                $query,
                $column,
                $this->keyGroupRelationRepository->getByKeyGroupId(
                    $filterValue->getKeyId(),
                    $filterValue->getGroupId()
                ),
                $this->groupConfigRepository->getById($filterValue->getGroupId()),
                $mode,
                $isValue,
                $fromValue,
                $toValue
            );
        }

        return $query;
    }

    private function validate(
        mixed $isValue,
        mixed $fromValue,
        mixed $toValue,
        string $mode
    ): void {
        $filterModes = FilterModes::values();
        if (!in_array($mode, $filterModes, true)) {
            throw new InvalidArgumentException(
                'Mode/setting must be on of: '. implode(', ', $filterModes)
            );
        }

        if (
            ($mode === FilterModes::IS->value && !is_numeric($isValue)) ||
            ($mode === FilterModes::BETWEEN->value && (!is_numeric($fromValue) || !is_numeric($toValue))) ||
            ($mode === FilterModes::LESS->value && is_numeric($toValue)) ||
            ($mode === FilterModes::MORE->value && !is_numeric($fromValue))
        ) {
            throw new InvalidArgumentException('Filter values must be numeric.');
        }
    }

    private function addFilterToQuery(
        DataObjectQueryInterface $query,
        ColumnFilter $column,
        KeyGroupRelation $key,
        GroupConfig $group,
        string $mode,
        int|float|null $isValue,
        int|float|null $fromValue,
        int|float|null $toValue
    ): void {
        match($mode) {
            FilterModes::IS->value =>
            $query->classificationStoreFilter(
                $column->getKeyWithOutLocale(),
                $group->getName(),
                new IntegerFilter(
                    $key->getName(),
                    $isValue,
                    true
                ),
                null
            ),

            FilterModes::BETWEEN->value =>
            $query->classificationStoreFilter(
                $column->getKeyWithOutLocale(),
                $group->getName(),
                new NumberRangeFilter(
                    $key->getName(),
                    $fromValue,
                    $toValue,
                    true
                ),
                null
            ),

            FilterModes::LESS->value =>
            $query->classificationStoreFilter(
                $column->getKeyWithOutLocale(),
                $group->getName(),
                new NumberRangeFilter(
                    $key->getName(),
                    null,
                    $toValue,
                    true
                ),
                null
            ),

            FilterModes::MORE->value =>
            $query->classificationStoreFilter(
                $column->getKeyWithOutLocale(),
                $group->getName(),
                new NumberRangeFilter(
                    $key->getName(),
                    $fromValue,
                    null,
                    true
                ),
                null
            ),

            default => throw new InvalidArgumentException(
                'Unable to apply number filter, unknown mode: '.$mode
            )
        };
    }
}
