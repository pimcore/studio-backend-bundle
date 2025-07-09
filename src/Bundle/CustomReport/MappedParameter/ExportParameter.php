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

namespace Pimcore\Bundle\StudioBackendBundle\Bundle\CustomReport\MappedParameter;

use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Util\StepConfig;

/**
 * @internal
 */
final readonly class ExportParameter extends ChartDataParameter
{
    public function __construct(
        string $name,
        FiltersParameter $filters = new FiltersParameter(),
        ?string $sortBy = null,
        ?string $sortOrder = null,
        int $page = 1,
        int $pageSize = 50,
        private bool $includeHeaders = false,
        private ?string $delimiter = null
    ) {
        parent::__construct(
            name: $name,
            filters: $filters,
            sortBy: $sortBy,
            sortOrder: $sortOrder,
            page: $page,
            pageSize: $pageSize
        );
    }

    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'],
            filters: new FiltersParameter(
                $data['filters']['columnFilters'],
                $data['filters']['drillDownFilters']
            ),
            sortBy: $data['sortBy'] ?? null,
            sortOrder: $data['sortOrder'] ?? null,
            page: $data['page'] ?? 1,
            pageSize: $data['pageSize'] ?? 50,
            includeHeaders: $data['includeHeaders'] ?? false,
            delimiter: $data[StepConfig::SETTINGS_DELIMITER->value] ?? null
        );
    }


    public function getIncludeHeaders(): bool
    {
        return $this->includeHeaders;
    }

    public function getDelimiter(): ?string
    {
        return $this->delimiter;
    }
}
