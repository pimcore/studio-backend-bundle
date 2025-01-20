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

namespace Pimcore\Bundle\StudioBackendBundle\CustomReport\Schema;

use OpenApi\Attributes\Items;
use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;
use Pimcore\Bundle\StudioBackendBundle\Util\Schema\AdditionalAttributesInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\AdditionalAttributesTrait;
use stdClass;

/**
 * @internal
 */
#[Schema(
    title: 'Custom Report Details',
    required: [
        'name',
        'sql',
        'dataSourceConfig',
        'columnConfiguration',
        'niceName',
        'groupIconClass',
        'iconClass',
        'menuShortcut',
        'reportClass',
        'chartType',
        'pieColumn',
        'pieLabelColumn',
        'xAxis',
        'yAxis',
        'modificationDate',
        'creationDate',
        'sharedUserNames',
        'sharedRoleNames',
        'sharedGlobally',
        'writeable',
    ],
    type: 'object',
)]
final class CustomReportDetails implements AdditionalAttributesInterface
{
    use AdditionalAttributesTrait;

    public function __construct(
        #[Property(description: 'Name', type: 'string', example: 'Quality_Attributes')]
        private readonly string $name,
        #[Property(description: 'Sql', type: 'string', example: '')]
        private readonly string $sql,
        #[Property(
            description: 'Configuration for data source. Content of array depends on selected adapter/data source',
            type: 'array',
            items: new Items(),
            example: '[]'
        )]
        private readonly stdClass $dataSourceConfig,
        #[Property(
            description: 'Configuration for columns to be displayed in report',
            type: 'array',
            items: new Items(CustomReportColumnConfiguration::class)
        )]
        /** array<CustomReportColumnConfiguration> */
        private readonly array $columnConfigurations,
        #[Property(description: 'Label/nice name of report', type: 'string', example: 'Attributes')]
        private readonly string $niceName,
        #[Property(description: 'Group icon class', type: 'string', example: 'pimcore_group_icon_attributes')]
        private readonly string $groupIconClass,
        #[Property(description: 'Icon class', type: 'string', example: 'pimcore_icon_attributes')]
        private readonly string $iconClass,
        #[Property(description: 'Whether the report has a shortcut in the menu', type: 'bool', example: true)]
        private readonly bool $menuShortcut,
        #[Property(
            description: 'Report class of custom report implementation',
            type: 'string',
            example: ''
        )]
        private readonly string $reportClass,
        #[Property(description: 'Chart type', type: 'string', example: 'pie')]
        private readonly string $chartType,
        #[Property(description: 'Modification date time stamp', type: 'int', example: 1736762668)]
        private readonly int $modificationDate,
        #[Property(description: 'Creation date time stamp', type: 'int', example: 1567409307)]
        private readonly int $creationDate,
        #[Property(
            description: 'Array with user names the report is shared with',
            type: 'array',
            items: new Items(type: 'string'),
            example: '["admin", "superuser"]'
        )]
        private readonly array $sharedUserNames,
        #[Property(
            description: 'Array with roles the report is shared with',
            type: 'array',
            items: new Items(type: 'string'),
            example: '["role", "role2"]'
        )]
        private readonly array $sharedRoleNames,
        #[Property(description: 'Whether the report is shared globally', type: 'bool', example: false)]
        private readonly bool $sharedGlobally,
        #[Property(description: 'Whether the report is writeable', type: 'bool', example: true)]
        private readonly bool $writeable,
        #[Property(description: 'Data column for pie chart', type: 'string', example: 'count(*)')]
        private readonly ?string $pieColumn = null,
        #[Property(description: 'Label of data column for pie chart', type: 'string', example: 'attributesAvailable')]
        private readonly ?string $pieLabelColumn = null,
        #[Property(
            description: 'X axis column names',
            type: 'array',
            items: new Items(type: 'string'),
            example: '["attributesAvailable", "count(*)"]'
        )]
        private readonly ?string $xAxis = null,
        #[Property(
            description: 'Y axis column information',
            type: 'array',
            items: new Items(type: 'string'),
            example: '["attributesAvailable"]'
        )]
        private readonly ?array $yAxis = null,
    ) {

    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getSql(): string
    {
        return $this->sql;
    }

    public function getDataSourceConfig(): stdClass
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

    public function getYAxis(): array
    {
        return $this->yAxis;
    }

    public function getModificationDate(): int
    {
        return $this->modificationDate;
    }

    public function getCreationDate(): int
    {
        return $this->creationDate;
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

    public function getWriteable(): bool
    {
        return $this->writeable;
    }
}
