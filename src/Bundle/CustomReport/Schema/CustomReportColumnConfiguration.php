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
    type: 'object',
)]
final readonly class CustomReportColumnConfiguration
{
    public function __construct(
        #[Property(description: 'Name', type: 'string', example: 'attributesAvailable')]
        private string $name,
        #[Property(description: 'Display name of column', type: 'bool', example: true)]
        private bool $display,
        #[Property(description: 'Whether the column should be included in exports', type: 'bool', example: true)]
        private bool $export,
        #[Property(description: 'Order', type: 'bool', example: true)]
        private bool $order,
        #[Property(description: 'Label/display name of column', type: 'string', example: 'Attributes')]
        private string $label,
        #[Property(description: 'Id', type: 'string', example: '401-3')]
        private string $id,
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

    public function getId(): string
    {
        return $this->id;
    }

    public function getFilterDrilldown(): ?string
    {
        return $this->filterDrilldown;
    }
}
