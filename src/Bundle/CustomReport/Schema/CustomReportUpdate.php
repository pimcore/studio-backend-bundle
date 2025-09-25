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

namespace Pimcore\Bundle\StudioBackendBundle\Bundle\CustomReport\Schema;

use OpenApi\Attributes\Items;
use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;

/**
 * @internal
 */
#[Schema(
    schema: 'BundleCustomReportUpdate',
    title: 'Bundle Custom Report Update',
    required: [
        'sql', 'dataSourceConfig', 'columnConfigurations', 'niceName', 'group', 'groupIconClass', 'iconClass',
        'menuShortcut', 'reportClass', 'chartType', 'pieColumn', 'pieLabelColumn', 'xAxis', 'yAxis',
        'sharedUserNames', 'sharedRoleNames', 'sharedGlobally',
    ],
    type: 'object',
)]
final readonly class CustomReportUpdate
{
    public function __construct(
        #[Property(description: 'Sql', type: 'string', example: '')]
        private string $sql,
        #[Property(
            description: 'Configuration for columns to be displayed in report',
            type: 'array',
            items: new Items(CustomReportColumnConfigurationUpdate::class)
        )]
        private array $columnConfigurations,
        #[Property(description: 'Label/nice name of report', type: 'string', example: 'Attributes')]
        private string $niceName,
        #[Property(description: 'Group of the report', type: 'string', example: 'My Reports')]
        private string $group,
        #[Property(description: 'Group icon class', type: 'string', example: 'pimcore_group_icon_attributes')]
        private string $groupIconClass,
        #[Property(description: 'Icon class', type: 'string', example: 'pimcore_icon_attributes')]
        private string $iconClass,
        #[Property(description: 'Whether the report has a shortcut in the menu', type: 'bool', example: true)]
        private bool $menuShortcut,
        #[Property(
            description: 'Report class of custom report implementation',
            type: 'string',
            example: ''
        )]
        private string $reportClass,
        #[Property(description: 'Chart type', type: 'string', example: 'pie')]
        private string $chartType,
        #[Property(
            description: 'Array with user names the report is shared with',
            type: 'array',
            items: new Items(type: 'string'),
            example: '["admin", "superuser"]'
        )]
        private array $sharedUserNames,
        #[Property(
            description: 'Array with roles the report is shared with',
            type: 'array',
            items: new Items(type: 'string'),
            example: '["role", "role2"]'
        )]
        private array $sharedRoleNames,
        #[Property(description: 'Whether the report is shared globally', type: 'bool', example: false)]
        private bool $sharedGlobally,
        #[Property(
            description: 'Configuration for data source. Content of array depends on selected adapter/data source',
            type: 'array',
            items: new Items(type: 'object'),
            example: [
                [
                    'sql' => 'carClass',
                    'from' => 'object_localized_CAR_en',
                    'where' => "objectType = 'actual-car'",
                    'groupby' => 'carClass',
                    'orderby' => '',
                    'orderbydir' => null,
                    'type' => 'sql',
                ],
            ]
        )]
        private array $dataSourceConfig = [],
        #[Property(description: 'Data column for pie chart', type: 'string', example: 'count(*)')]
        private ?string $pieColumn = null,
        #[Property(description: 'Label of data column for pie chart', type: 'string', example: 'attributesAvailable')]
        private ?string $pieLabelColumn = null,
        #[Property(description: 'X axis column names', type: 'string', example: 'attributesAvailable')]
        private ?string $xAxis = null,
        #[Property(
            description: 'Y axis column information',
            type: 'array',
            items: new Items(type: 'string'),
            example: '["attributesAvailable", "count(*)"]'
        )]
        private ?array $yAxis = null,
    ) {
    }

    public function getSql(): string
    {
        return $this->sql;
    }

    public function getDataSourceConfig(): ?array
    {
        return $this->dataSourceConfig;
    }

    public function getColumnConfigurations(): array
    {
        return $this->columnConfigurations;
    }

    public function getMenuShortcut(): bool
    {
        return $this->menuShortcut;
    }

    public function getNiceName(): string
    {
        return $this->niceName;
    }

    public function getGroup(): string
    {
        return $this->group;
    }

    public function getGroupIconClass(): string
    {
        return $this->groupIconClass;
    }

    public function getIconClass(): string
    {
        return $this->iconClass;
    }

    public function getReportClass(): string
    {
        return $this->reportClass;
    }

    public function getChartType(): string
    {
        return $this->chartType;
    }

    public function getPieColumn(): ?string
    {
        return $this->pieColumn;
    }

    public function getPieLabelColumn(): ?string
    {
        return $this->pieLabelColumn;
    }

    public function getXAxis(): ?string
    {
        return $this->xAxis;
    }

    public function getYAxis(): ?array
    {
        return $this->yAxis;
    }

    public function getSharedUserNames(): array
    {
        return $this->sharedUserNames;
    }

    public function getSharedRoleNames(): array
    {
        return $this->sharedRoleNames;
    }

    public function getSharedGlobally(): bool
    {
        return $this->sharedGlobally;
    }
}
