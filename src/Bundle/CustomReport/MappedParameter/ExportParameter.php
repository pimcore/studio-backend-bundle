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

use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use function in_array;

/**
 * @internal
 */
final readonly class ExportParameter
{
    public function __construct(
        private string $name,
        private ?array $filters = null,
        private ?array $drillDownFilters = null,
        private ?string $sortOrder = null,
        private ?string $sortBy = null,
        private ?int $reportOffset = null,
        private ?int $reportLimit = null,
        private ?array $fields = null,
        private bool $includeHeaders = false,
        private ?string $defaultDelimiter = null
    ) {
        $this->validate();
    }

    public function getSortOrder(): ?string
    {
        return $this->sortOrder;
    }

    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'],
            filters: $data['filters'] ?? null,
            drillDownFilters: $data['drillDownFilters'] ?? null,
            sortOrder: $data['sortOrder'] ?? null,
            sortBy: $data['sortBy'] ?? null,
            reportOffset: $data['reportOffset'] ?? null,
            reportLimit: $data['reportLimit'] ?? null,
            fields: $data['fields'] ?? null,
            includeHeaders: $data['includeHeaders'] ?? false,
            defaultDelimiter: $data['defaultDelimiter'] ?? null
        );
    }

    public function getSortBy(): ?string
    {
        return $this->sortBy;
    }

    public function getReportLimit(): ?int
    {
        return $this->reportLimit;
    }

    public function getReportOffset(): ?int
    {
        return $this->reportOffset;
    }

    private function validate(): void
    {
        if ($this->getSortOrder()  && !in_array($this->getSortOrder(), ['ASC', 'DESC'])) {
            throw new InvalidArgumentException('Invalid sort order');
        }
    }

    public function getFilters(): ?array
    {
        return $this->filters;
    }

    public function getDrillDownFilters(): ?array
    {
        return $this->drillDownFilters;
    }

    public function getFields(): ?array
    {
        return $this->fields;
    }

    public function getIncludeHeaders(): bool
    {
        return $this->includeHeaders;
    }

    public function getDefaultDelimiter(): ?string
    {
        return $this->defaultDelimiter;
    }

    public function getName(): string
    {
        return $this->name;
    }
}
