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
    /**
     * Only paginate the export when the request explicitly asks for a page;
     * otherwise the export contains all rows of the report.
     */
    private bool $paginated;

    public function __construct(
        string $name,
        ?array $fields = null,
        FiltersParameter $filters = new FiltersParameter(),
        ?string $sortBy = null,
        ?string $sortOrder = null,
        ?int $page = null,
        ?int $pageSize = null,
        private bool $includeHeaders = false,
        private ?string $delimiter = null
    ) {
        $this->paginated = $page !== null || $pageSize !== null;

        parent::__construct(
            name: $name,
            fields: $fields,
            filters: $filters,
            sortBy: $sortBy,
            sortOrder: $sortOrder,
            page: $page ?? 1,
            pageSize: $pageSize ?? 50
        );
    }

    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'],
            fields: $data['fields'],
            filters: new FiltersParameter(
                $data['filters']['columnFilters'],
                $data['filters']['drillDownFilters']
            ),
            sortBy: $data['sortBy'] ?? null,
            sortOrder: $data['sortOrder'] ?? null,
            page: ($data['paginated'] ?? false) ? ($data['page'] ?? null) : null,
            pageSize: ($data['paginated'] ?? false) ? ($data['pageSize'] ?? null) : null,
            includeHeaders: $data['includeHeaders'] ?? false,
            delimiter: $data[StepConfig::SETTINGS_DELIMITER->value] ?? null
        );
    }

    public function isPaginated(): bool
    {
        return $this->paginated;
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
