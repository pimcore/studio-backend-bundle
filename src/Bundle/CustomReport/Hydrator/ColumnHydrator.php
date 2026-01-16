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

use Exception;
use Pimcore\Bundle\CustomReportsBundle\Tool\Config;
use Pimcore\Bundle\CustomReportsBundle\Tool\Config\ColumnInformation;
use Pimcore\Bundle\StudioBackendBundle\Bundle\CustomReport\Schema\CustomReportColumnConfiguration;
use Pimcore\Bundle\StudioBackendBundle\Bundle\CustomReport\Schema\CustomReportColumnInformation;
use Pimcore\Bundle\StudioBackendBundle\Bundle\CustomReport\Service\AdapterServiceInterface;
use function is_int;

/**
 * @internal
 */
final readonly class ColumnHydrator implements ColumnHydratorInterface
{
    public function __construct(
        private AdapterServiceInterface $adapterService,
    ) {
    }

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

    public function getCustomReportColumnConfiguration(Config $report): array
    {
        $columnConfig = [];
        $metadataMap = $this->getMetadataMap($report);
        foreach ($report->getColumnConfiguration() as $column) {
            /** @var ColumnInformation|null $metadata */
            $metadata = $metadataMap[$column['name']] ?? null;
            $width = $column['width'] ?? null;
            $columnConfig[] = new CustomReportColumnConfiguration(
                $column['name'] ?? '',
                $column['display'] ?? '',
                $column['export'] ?? '',
                $column['order'] ?? '',
                $column['label'] ?? '',
                $column['action'] ?? '',
                $column['id'] ?? '',
                is_int($width) ? $width : null,
                $column['displayType'] ?? null,
                $column['filterType'] ?? null,
                $column['filter_drilldown'] ?? null,
                $metadata && $metadata->isDisableOrderBy(),
                $metadata && $metadata->isDisableFilterable(),
                $metadata && $metadata->isDisableDropdownFilterable(),
                $metadata && $metadata->isDisableLabel()
            );
        }

        return $columnConfig;
    }

    public function dehydrateColumnConfiguration(array $columnConfigurations): array
    {
        $dehydrated = [];
        foreach ($columnConfigurations as $configuration) {
            $dehydrated[] = [
                'name' => $configuration['name'],
                'display' => $configuration['display'],
                'export' => $configuration['export'],
                'order' => $configuration['order'],
                'label' => $configuration['label'],
                'action' => $configuration['action'],
                'id' => $configuration['id'],
                'width' => $configuration['width'],
                'displayType' => $configuration['displayType'],
                'filterType' => $configuration['filterType'],
                'filter_drilldown' => $configuration['filterDrilldown'],
            ];
        }

        return $dehydrated;
    }

    private function getMetadataMap(Config $report): array
    {
        $adapter = $this->adapterService->getAdapter($report);

        try {
            $metadata = $adapter->getColumnsWithMetadata($report->getDataSourceConfig());
            $columnNames = array_map(static fn ($column) => $column->getName(), $metadata);

            return array_combine($columnNames, $metadata);
        } catch (Exception) {
            return [];
        }
    }
}
