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

namespace Pimcore\Bundle\StudioBackendBundle\Class\Service\BulkImport;

use Pimcore\Bundle\StudioBackendBundle\Class\Util\ClassDefinitionType;
use function count;
use function is_array;

/**
 * @internal
 */
final readonly class BulkImportDataResolver
{
    public function resolveEntryName(ClassDefinitionType $type, array $exportEntry): ?string
    {
        return match ($type) {
            ClassDefinitionType::FieldCollection,
            ClassDefinitionType::ObjectBrick => $exportEntry['key'] ?? null,
            ClassDefinitionType::ClassDefinition,
            ClassDefinitionType::CustomLayout => $exportEntry['name'] ?? null,
        };
    }

    /**
     * @return array<string, true>
     */
    public function buildRequestedItemsIndex(array $items): array
    {
        $index = [];
        foreach ($items as $item) {
            $type = $item['type'] ?? null;
            $name = $item['name'] ?? null;
            if ($type !== null && $name !== null) {
                $index[$type . '::' . $name] = true;
            }
        }

        return $index;
    }

    /**
     * Determines which import types actually have matching requested items in the file data.
     *
     * @param array<string, true> $requestedItems
     *
     * @return array<string, true>
     */
    public function resolveRequestedTypes(array $fileData, array $requestedItems): array
    {
        $types = [];

        foreach (ClassDefinitionType::importOrder() as $type) {
            $dataForType = $fileData[$type->value] ?? [];

            if (!is_array($dataForType) || empty($dataForType)) {
                continue;
            }

            foreach ($dataForType as $exportEntry) {
                if (!is_array($exportEntry)) {
                    continue;
                }

                $name = $this->resolveEntryName($type, $exportEntry);
                if ($name === null) {
                    continue;
                }

                $itemKey = $type->value . '::' . $name;
                if (isset($requestedItems[$itemKey])) {
                    $types[$type->value] = true;

                    break;
                }
            }
        }

        return $types;
    }

    /**
     * Filters export entries for a given type, returning only those that match requested items.
     *
     * @param array<string, true> $requestedIndex
     *
     * @return array{items: array<array{name: string, entry: array}>, count: int}
     */
    public function filterItemsForType(
        array $dataForType,
        ClassDefinitionType $type,
        array $requestedIndex,
    ): array {
        $filtered = [];
        foreach ($dataForType as $exportEntry) {
            if (!is_array($exportEntry)) {
                continue;
            }
            $name = $this->resolveEntryName($type, $exportEntry);
            if ($name === null) {
                continue;
            }

            $itemKey = $type->value . '::' . $name;
            if (isset($requestedIndex[$itemKey])) {
                $filtered[] = ['name' => $name, 'entry' => $exportEntry];
            }
        }

        return ['items' => $filtered, 'count' => count($filtered)];
    }
}
