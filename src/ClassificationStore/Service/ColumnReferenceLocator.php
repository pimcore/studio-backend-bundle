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

namespace Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service;

use function array_filter;
use function is_array;
use function is_int;
use function is_string;
use function str_starts_with;

/**
 * @internal
 */
final readonly class ColumnReferenceLocator implements ColumnReferenceLocatorInterface
{
    private const string ADVANCED_TYPE = 'dataobject.advanced';

    private const string CLASSIFICATION_STORE_TYPE = 'dataobject.classificationstore';

    private const string FILTER_TYPE_PREFIX = 'classificationstore.';

    /**
     * {@inheritdoc}
     */
    public function references(array $columns): array
    {
        $references = [];
        foreach ($columns as $index => $column) {
            $type = $column['type'] ?? null;
            if ($type === self::CLASSIFICATION_STORE_TYPE && is_string($column['key'] ?? null)) {
                $references[] = [$index, null, $column['key']];
            }

            if ($type === self::ADVANCED_TYPE) {
                foreach ($this->advancedReferences($index, $column) as $reference) {
                    $references[] = $reference;
                }
            }
        }

        return $references;
    }

    /**
     * {@inheritdoc}
     */
    public function identifierReferences(array $columns): array
    {
        return array_filter(
            $this->references($columns),
            fn (array $reference) => $this->hasIdentifiers($this->configAt($columns, $reference))
        );
    }

    /**
     * {@inheritdoc}
     */
    public function pairReferences(array $columns): array
    {
        return array_filter(
            $this->references($columns),
            fn (array $reference) => $this->hasIdPair($this->configAt($columns, $reference))
                && !$this->hasIdentifiers($this->configAt($columns, $reference))
        );
    }

    /**
     * {@inheritdoc}
     */
    public function filterColumns(array $columnFilters): array
    {
        $columns = [];
        foreach ($columnFilters as $index => $columnFilter) {
            if (!is_array($columnFilter) || !$this->isClassificationStoreFilter($columnFilter)) {
                continue;
            }

            $columns[$index] = [
                'type' => self::CLASSIFICATION_STORE_TYPE,
                'key' => $columnFilter['key'],
                'config' => $columnFilter['filterValue'],
            ];
        }

        return $columns;
    }

    /**
     * {@inheritdoc}
     */
    public function configAt(array $columns, array $reference): array
    {
        [$index, $advancedIndex] = $reference;
        $config = $advancedIndex === null
            ? $columns[$index]['config'] ?? []
            : $columns[$index]['config']['advancedColumns'][$advancedIndex]['config'] ?? [];

        return is_array($config) ? $config : [];
    }

    /**
     * {@inheritdoc}
     */
    public function replaceConfig(array $columns, array $reference, array $config): array
    {
        [$index, $advancedIndex] = $reference;

        if ($advancedIndex === null) {
            $columns[$index]['config'] = $config;

            return $columns;
        }

        $columns[$index]['config']['advancedColumns'][$advancedIndex]['config'] = $config;

        return $columns;
    }

    public function hasIdentifiers(array $config): bool
    {
        return $this->isFilled($config[self::GROUP_IDENTIFIER] ?? null)
            && $this->isFilled($config[self::KEY_IDENTIFIER] ?? null);
    }

    public function hasIdPair(array $config): bool
    {
        return is_int($config[self::GROUP_ID] ?? null) && is_int($config[self::KEY_ID] ?? null);
    }

    /**
     * The source fields of an advanced column; a relation field names its field on the related class.
     *
     * @return array<int, array{0: int, 1: int, 2: string, 3: string|null}>
     */
    private function advancedReferences(int $index, array $column): array
    {
        $references = [];
        $advancedColumns = $column['config']['advancedColumns'] ?? [];

        foreach (is_array($advancedColumns) ? $advancedColumns : [] as $advancedIndex => $advancedColumn) {
            $field = $advancedColumn['config']['field'] ?? null;
            $relation = $advancedColumn['config']['relation'] ?? null;
            if (!is_string($field)) {
                continue;
            }

            if (($advancedColumn['key'] ?? null) === 'simpleField') {
                $references[] = [$index, $advancedIndex, $field, null];
            }

            if (($advancedColumn['key'] ?? null) === 'relationField' && is_string($relation)) {
                $references[] = [$index, $advancedIndex, $field, $relation];
            }
        }

        return $references;
    }

    private function isClassificationStoreFilter(array $columnFilter): bool
    {
        return is_string($columnFilter['type'] ?? null)
            && str_starts_with($columnFilter['type'], self::FILTER_TYPE_PREFIX)
            && is_string($columnFilter['key'] ?? null)
            && is_array($columnFilter['filterValue'] ?? null);
    }

    private function isFilled(mixed $identifier): bool
    {
        return is_string($identifier) && $identifier !== '';
    }
}
