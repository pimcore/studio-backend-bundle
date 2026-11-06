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

namespace Pimcore\Bundle\StudioBackendBundle\DataIndex\Filter;

use Pimcore\Bundle\StudioBackendBundle\DataIndex\Query\AssetQueryInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Query\DataObjectQueryInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Query\DocumentQueryInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Query\QueryInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Filter\FilterOperator;
use Pimcore\Bundle\StudioBackendBundle\Grid\Column\ColumnType;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\Filter\ColumnFilter;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\Filter\ColumnFiltersParameterInterface;
use function in_array;
use function preg_match;
use function sprintf;

/**
 * Finds elements where a field has no value (empty) or any value (notEmpty), whatever the type of the field is.
 *
 * @internal
 */
final class EmptyFilter implements FilterInterface
{
    use MapsSystemColumnFieldTrait;

    /**
     * Same grammar PQL accepts for field names: identifiers joined by dots.
     */
    private const string FIELD_NAME_PATTERN = '/^[a-zA-Z_]\w*(\.[a-zA-Z_]\w*)*$/D';

    /**
     * System columns of assets and documents that are not an index field of their own. Their file name is the key of
     * the element.
     */
    private const array ELEMENT_FIELD_ALIASES = [
        'filename' => 'key',
    ];

    /**
     * Types whose index field can hold an empty string. Only there "no value" has to match the empty string as well,
     * on every other type PQL cannot compare with an empty string.
     */
    private const array TEXT_TYPES = [
        ColumnType::SYSTEM_STRING,
        ColumnType::SYSTEM_SELECT,
        ColumnType::METADATA_STRING,
        ColumnType::METADATA_INPUT,
        ColumnType::METADATA_TEXTAREA,
        ColumnType::METADATA_SELECT,
    ];

    /**
     * @throws InvalidArgumentException
     */
    public function apply(mixed $parameters, QueryInterface $query): QueryInterface
    {
        if (!$parameters instanceof ColumnFiltersParameterInterface) {
            return $query;
        }

        foreach ($parameters->getOperatorColumnFilters() as $column) {
            $query = $query->filterByPql($this->buildPql($column, $query));
        }

        return $query;
    }

    /**
     * @throws InvalidArgumentException
     */
    private function buildPql(ColumnFilter $column, QueryInterface $query): string
    {
        $field = $this->mapToIndexField($column, $query);

        if (!preg_match(self::FIELD_NAME_PATTERN, $field)) {
            throw new InvalidArgumentException(sprintf('Invalid field name "%s" for an empty filter', $field));
        }

        $value = $this->isTextType($column->getType()) ? 'EMPTY' : 'NULL';

        return match ($column->getOperator()) {
            FilterOperator::EMPTY => sprintf('%s = %s', $field, $value),
            FilterOperator::NOT_EMPTY => sprintf('%s != %s', $field, $value),
            default => throw new InvalidArgumentException('Filter has no operator'),
        };
    }

    /**
     * The grid names of the system columns differ from the index field in some cases, which depends on the kind of
     * element. Fields of the same name that are not a system column (e.g. metadata or a field of a data object class)
     * are searched under their own name.
     */
    private function mapToIndexField(ColumnFilter $column, QueryInterface $query): string
    {
        $field = $column->getKey();

        if ($column->getType() !== ColumnType::SYSTEM_STRING->value) {
            return $field;
        }

        if ($query instanceof DataObjectQueryInterface) {
            return $this->mapColumnKeyToIndexField($field);
        }

        if ($query instanceof AssetQueryInterface || $query instanceof DocumentQueryInterface) {
            return self::ELEMENT_FIELD_ALIASES[$field] ?? $field;
        }

        return $field;
    }

    private function isTextType(string $type): bool
    {
        $columnType = ColumnType::tryFrom($type);

        return $columnType !== null && in_array($columnType, self::TEXT_TYPES, true);
    }
}
