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
                $column['columnAction'] ?? '',
                $column['id'] ?? '',
                is_int($width) ? $width : null,
                $column['displayType'] ?? null,
                $column['filter'] ?? null,
                $column['filter_drilldown'] ?? null,
                $metadata?->isDisableOrderBy(),
                $metadata?->isDisableFilterable(),
                $metadata?->isDisableDropdownFilterable(),
                $metadata?->isDisableLabel()
            );
        }

        return $columnConfig;
    }

    private function getMetadataMap(Config $report): array
    {
        $adapter = $this->adapterService->getAdapter($report);
        $metadata = $adapter->getColumnsWithMetadata($report->getDataSourceConfig());
        $columnNames = array_map(static fn ($column) => $column->getName(), $metadata);

        return array_combine($columnNames, $metadata);
    }
}
