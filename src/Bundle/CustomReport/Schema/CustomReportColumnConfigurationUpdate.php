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
    schema: 'BundleCustomReportsColumnConfigurationUpdate',
    title: 'Bundle Custom Reports Column Configuration Update Data',
    required: [
        'name', 'display', 'export', 'order', 'label', 'action',
        'id', 'width', 'displayType', 'filterType', 'filterDrilldown',
    ],
    type: 'object',
)]
readonly class CustomReportColumnConfigurationUpdate
{
    public function __construct(
        #[Property(description: 'Name', type: 'string', example: 'attributesAvailable')]
        private string $name,
        #[Property(description: 'Display column', type: 'bool', example: true)]
        private bool $display,
        #[Property(description: 'Whether the column should be included in exports', type: 'bool', example: true)]
        private bool $export,
        #[Property(description: 'Order', type: 'bool', example: true)]
        private bool $order,
        #[Property(description: 'Label/display name of column', type: 'string', example: 'Attributes')]
        private string $label,
        #[Property(description: 'Action of the column', type: 'string', example: 'openObject')]
        private string $action,
        #[Property(description: 'Id', type: 'string', example: '401-3')]
        private string $id,
        #[Property(description: 'Width of the column', type: 'integer', example: 200)]
        private ?int $width = null,
        #[Property(description: 'Display type of the column', type: 'string', example: 'text')]
        private ?string $displayType = null,
        #[Property(description: 'Type of the filter', type: 'string', example: 'numeric')]
        private ?string $filterType = null,
        #[Property(description: 'Drilldown filter', type: 'string', example: 'only_filter')]
        private ?string $filterDrilldown = null,
    ) {

    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDisplay(): bool
    {
        return $this->display;
    }

    public function getDisplayType(): ?string
    {
        return $this->displayType;
    }

    public function getExport(): bool
    {
        return $this->export;
    }

    public function getOrder(): bool
    {
        return $this->order;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function getAction(): string
    {
        return $this->action;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getWidth(): ?int
    {
        return $this->width;
    }

    public function getFilterType(): ?string
    {
        return $this->filterType;
    }

    public function getFilterDrilldown(): ?string
    {
        return $this->filterDrilldown;
    }
}
