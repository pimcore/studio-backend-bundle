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
readonly class ChartDataParameter
{
    public function __construct(
        private string $name,
        private ?array $fields = null,
        private FiltersParameter $filters = new FiltersParameter(),
        private ?string $sortBy = null,
        private ?string $sortOrder = null,
        private int $page = 1,
        private int $pageSize = 50
    ) {
        $this->validate();
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getFields(): ?array
    {
        return $this->fields;
    }

    public function getFilters(): FiltersParameter
    {
        return $this->filters;
    }

    public function getSortBy(): ?string
    {
        return $this->sortBy;
    }

    public function getSortOrder(): ?string
    {
        return $this->sortOrder;
    }

    public function getPage(): int
    {
        return $this->page;
    }

    public function getPageSize(): int
    {
        return $this->pageSize;
    }

    private function validate(): void
    {
        if ($this->getSortOrder()  && !in_array($this->getSortOrder(), ['ASC', 'DESC'])) {
            throw new InvalidArgumentException(
                'Invalid sort order. Expected "ASC" or "DESC", got "' . $this->getSortOrder() . '".'
            );
        }
    }
}
