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

use Exception;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Service\DataAdapterLoaderInterface;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\ClassDefinition\Data\StructuredTable;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\DataObject\Data\StructuredTable as StructuredTableData;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

/**
 * @internal
 */
#[AutoconfigureTag(DataAdapterLoaderInterface::ADAPTER_TAG)]
final class StructuredTableAdapter extends AbstractAdapter
{
    /**
     * @throws Exception
     */
    public function getDataForSetter(
        Concrete $element, Data $fieldDefinition, string $key, array $data
    ): ?StructuredTableData
    {
        if (!array_key_exists($key, $data)) {
            return null;
        }

        $table = new StructuredTableData();
        $tableData = [];
        foreach ($data[$key] as $dataLine) {
            /** @var StructuredTable $fieldDefinition */
            $cols = $fieldDefinition->getCols();
            foreach ($cols as $col) {
                $tableData[$dataLine['__row_identifyer']][$col['key']] = $dataLine[$col['key']];
            }
        }
        $table->setData($tableData);

        return $table;
    }

    public function supports(string $fieldDefinitionClass): bool
    {
        return $fieldDefinitionClass === StructuredTable::class;
    }
}
