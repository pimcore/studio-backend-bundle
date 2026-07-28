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
use Pimcore\Bundle\StudioBackendBundle\Grid\Column\ColumnType;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\Filter\ColumnFilter;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\Filter\ColumnFiltersParameterInterface;
use function is_array;

/**
 * Filters the asset file size (stored in bytes). The client sends a value plus a size unit
 * (KB/MB/GB); this handler converts the value to bytes and applies it as a numeric range, mirroring
 * how the datetime filter rounds a coarse "on" day to a full-day range. Because the user works at a
 * coarser precision than the stored bytes, the "is" setting matches a one-unit-wide band rather than
 * an exact byte value.
 *
 * @internal
 */
final class FileSizeFilter implements FilterInterface
{
    private const BYTE_MULTIPLIER = [
        'KB' => 1024,
        'MB' => 1024 * 1024,
        'GB' => 1024 * 1024 * 1024,
    ];

    public function apply(mixed $parameters, QueryInterface $query): QueryInterface
    {
        if (!$parameters instanceof ColumnFiltersParameterInterface) {
            return $query;
        }

        foreach ($parameters->getColumnFilterByType(ColumnType::SYSTEM_FILE_SIZE->value) as $column) {
            $query = $this->applyFileSizeFilter($column, $query);
        }

        return $query;
    }

    private function applyFileSizeFilter(ColumnFilter $column, QueryInterface $query): QueryInterface
    {
        $filterValue = $column->getFilterValue();

        if (!is_array($filterValue) || !isset($filterValue['setting'])) {
            throw new InvalidArgumentException('This filter requires a setting value');
        }

        $multiplier = $this->getMultiplier($filterValue['unit'] ?? null);
        $field = $column->getKey();
        $setting = $filterValue['setting'];
        $toBytes = static fn (int|float $value): int => (int) round($value * $multiplier);

        if ($setting === 'is' && isset($filterValue['is'])) {
            $lower = $toBytes($filterValue['is']);

            // One-unit-wide band, so "is 1 MB" matches everything that reads as 1-point-something MB
            // (the byte-precise value would practically never match on its own). filterNumberRange
            // bounds are exclusive (gt/lt), so widen by one byte on each side to make the band
            // [lower, lower + unit - 1] inclusive.
            return $query->filterNumberRange($field, $lower - 1, $lower + $multiplier);
        }

        if ($setting === 'less' && isset($filterValue['to'])) {
            return $query->filterNumberRange($field, null, $toBytes($filterValue['to']));
        }

        if ($setting === 'more' && isset($filterValue['from'])) {
            return $query->filterNumberRange($field, $toBytes($filterValue['from']), null);
        }

        if ($setting === 'between' && (isset($filterValue['from']) || isset($filterValue['to']))) {
            return $query->filterNumberRange(
                $field,
                isset($filterValue['from']) ? $toBytes($filterValue['from']) : null,
                isset($filterValue['to']) ? $toBytes($filterValue['to']) : null
            );
        }

        throw new InvalidArgumentException('Unable to apply file size filter, no correct setting given');
    }

    private function getMultiplier(mixed $unit): int
    {
        if (!is_string($unit) || !isset(self::BYTE_MULTIPLIER[$unit])) {
            throw new InvalidArgumentException('File size filter requires a valid unit (KB, MB or GB)');
        }

        return self::BYTE_MULTIPLIER[$unit];
    }
}
