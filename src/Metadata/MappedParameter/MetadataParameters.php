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

namespace Pimcore\Bundle\StudioBackendBundle\Metadata\MappedParameter;

use Pimcore\Bundle\StudioBackendBundle\MappedParameter\Filter\SortFilter;

/**
 * @internal
 */
final readonly class MetadataParameters
{
    public function __construct(
        private ?string $searchTerm = null,
        private array $columnFilters = [],
        private ?SortFilter $sortFilter = null,
    ) {
    }

    public function getSearchTerm(): ?string
    {
        return $this->searchTerm;
    }

    public function getColumnFilters(): array
    {
        return $this->columnFilters;
    }

    public function getSortFilter(): ?SortFilter
    {
        return $this->sortFilter;
    }
}
