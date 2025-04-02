<?php
declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Bundle\StudioBackendBundle\DataObject\Data\Adapter;

use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\DataNormalizerInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\Model\FieldContextData;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\SetterDataInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Service\DataAdapterLoaderInterface;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\ClassDefinition\Data\StructuredTable as StructuredTableDefinition;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\DataObject\Data\StructuredTable;
use Pimcore\Model\UserInterface;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

/**
 * @internal
 */
#[AutoconfigureTag(DataAdapterLoaderInterface::ADAPTER_TAG)]
final readonly class StructuredTableAdapter implements SetterDataInterface, DataNormalizerInterface
{
    public function getDataForSetter(
        Concrete $element,
        Data $fieldDefinition,
        string $key,
        array $data,
        UserInterface $user,
        ?FieldContextData $contextData = null,
        bool $isPatch = false
    ): ?StructuredTable {
        $table = new StructuredTable();
        $tableData = [];
        foreach ($data[$key] as $id => $dataLine) {
            /** @var StructuredTableDefinition $fieldDefinition */
            $cols = $fieldDefinition->getCols();
            foreach ($cols as $col) {
                $tableData[$id][$col['key']] = $this->transformBoolValues($dataLine[$col['key']]);
            }
        }
        $table->setData($tableData);

        return $table;
    }
    
    public function normalize(mixed $value, Data $fieldDefinition): ?array
    {
        if ($value === null) {
            return null;
        }

        if (!$fieldDefinition instanceof StructuredTableDefinition) {
            return null;
        }

        $rows = $fieldDefinition->normalize($value);

        $returnValue = [];
        foreach ($rows as $rowKey => $columns) {
            foreach ($columns as $colKey => $colValue) {
                if ($colKey !== '' && $rowKey !== '') {
                    $colType = $this->getColumnType($fieldDefinition, $colKey);
                    $returnValue[$rowKey][$colKey] = $colType === 'bool' ? (bool)$colValue: $colValue;
                }
            }
        }

        return $returnValue;
    }

    private function getColumnType(StructuredTableDefinition $definition, string $colKey): ?string
    {
        $cols = $definition->getCols();
        foreach ($cols as $col) {
            if ($col['key'] === $colKey) {
                return $col['type'];
            }
        }

        return null;
    }

    private function transformBoolValues(mixed $value): mixed
    {
        if (is_bool($value)) {
            return $value ? 1 : 0;
        }

        return $value;
    }
}
