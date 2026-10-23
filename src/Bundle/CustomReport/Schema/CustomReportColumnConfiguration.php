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

use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;

/**
 * @internal
 */
#[Schema(
    schema: 'BundleCustomReportsColumnConfiguration',
    title: 'Bundle Custom Reports Column Configuration',
    required: ['disableOrderBy', 'disableFilterable', 'disableDropdownFilterable', 'disableLabel'],
    type: 'object',
)]
final readonly class CustomReportColumnConfiguration extends CustomReportColumnConfigurationUpdate
{
    public function __construct(
        #[Property(description: 'Name', type: 'string', example: 'attributesAvailable')]
        string $name,
        #[Property(description: 'Display column', type: 'bool', example: true)]
        bool $display,
        #[Property(description: 'Whether the column should be included in exports', type: 'bool', example: true)]
        bool $export,
        #[Property(description: 'Order', type: 'bool', example: true)]
        bool $order,
        #[Property(description: 'Label/display name of column', type: 'string', example: 'Attributes')]
        string $label,
        #[Property(description: 'Action of the column', type: 'string', example: 'openObject')]
        string $action,
        #[Property(description: 'Id', type: 'string', example: '401-3')]
        string $id,
        #[Property(description: 'Width of the column', type: 'integer', example: 200)]
        ?int $width = null,
        #[Property(description: 'Display type of the column', type: 'string', example: 'text')]
        ?string $displayType = null,
        #[Property(description: 'Type of the filter', type: 'string', example: 'numeric')]
        ?string $filterType = null,
        #[Property(description: 'Drilldown filter', type: 'string', example: 'only_filter')]
        ?string $filterDrilldown = null,
        #[Property(description: 'Disable order by', type: 'bool', example: false)]
        private bool $disableOrderBy = false,
        #[Property(description: 'Disable filterable', type: 'bool', example: false)]
        private bool $disableFilterable = false,
        #[Property(description: 'Disable dropdown filterable', type: 'bool', example: false)]
        private bool $disableDropdownFilterable = false,
        #[Property(description: 'Disable label', type: 'bool', example: false)]
        private bool $disableLabel = false,
    ) {
        parent::__construct(
            name: $name,
            display: $display,
            export: $export,
            order: $order,
            label: $label,
            action: $action,
            id: $id,
            width: $width,
            displayType: $displayType,
            filterType: $filterType,
            filterDrilldown: $filterDrilldown
        );
    }

    public function isDisableOrderBy(): bool
    {
        return $this->disableOrderBy;
    }

    public function isDisableFilterable(): bool
    {
        return $this->disableFilterable;
    }

    public function isDisableDropdownFilterable(): bool
    {
        return $this->disableDropdownFilterable;
    }

    public function isDisableLabel(): bool
    {
        return $this->disableLabel;
    }
}
