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

namespace Pimcore\Bundle\StudioBackendBundle\Bundle\CustomReport\Hydrator;

use Pimcore\Bundle\CustomReportsBundle\Tool\Config\ColumnInformation;
use Pimcore\Bundle\StudioBackendBundle\Bundle\CustomReport\Schema\CustomReportColumnConfiguration;
use Pimcore\Bundle\StudioBackendBundle\Bundle\CustomReport\Schema\CustomReportColumnInformation;
use function is_int;

/**
 * @internal
 */
final readonly class ColumnHydrator implements ColumnHydratorInterface
{
    public function hydrateColumnInfo(ColumnInformation $information): CustomReportColumnInformation
    {
        return new CustomReportColumnInformation(
            $information->getName(),
            $information->isDisableOrderBy(),
            $information->isDisableFilterable(),
            $information->isDisableDropdownFilterable(),
            $information->isDisableLabel()
        );
    }

    /**
     * @param ColumnInformation[] $metaData
     */
    public function getCustomReportColumnConfiguration(array $columns, array $metaData): array
    {
        $columnConfig = [];
        foreach ($columns as $column) {
            $width = $column['width'] ?? null;
            $columnConfig[] = new CustomReportColumnConfiguration(
                $column['name'] ?? '',
                $column['display'] ?? '',
                $column['export'] ?? '',
                $column['order'] ?? '',
                $column['label'] ?? '',
                $column['columnAction'] ?? '',
                $column['id'] ?? '',
                is_int($width) ? $width : null,
                $column['displayType'] ?? null,
                $column['filter'] ?? null,
                $column['filter_drilldown'] ?? null
            );
        }

        return $columnConfig;
    }
}
