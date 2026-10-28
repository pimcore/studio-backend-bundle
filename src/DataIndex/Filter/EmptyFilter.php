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
     * Grid columns that are not an index field of their own. The file name of an asset is the key of the element.
     */
    private const array FIELD_ALIASES = [
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
            $query = $query->filterByPql($this->buildPql($column));
        }

        return $query;
    }

    /**
     * @throws InvalidArgumentException
     */
    private function buildPql(ColumnFilter $column): string
    {
        $field = $this->mapColumnKeyToIndexField($column->getKey());
        $field = self::FIELD_ALIASES[$field] ?? $field;

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

    private function isTextType(string $type): bool
    {
        $columnType = ColumnType::tryFrom($type);

        return $columnType !== null && in_array($columnType, self::TEXT_TYPES, true);
    }
}
