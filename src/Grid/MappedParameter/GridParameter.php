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

namespace Pimcore\Bundle\StudioBackendBundle\Grid\MappedParameter;

use Pimcore\Bundle\StudioBackendBundle\Filter\MappedParameter\FilterParameter;

/**
 * @internal
 */
final readonly class GridParameter
{
    public function __construct(
        private int $folderId,
        private array $columns,
        private ?FilterParameter $filters,
    ) {
    }

    public function getFolderId(): int
    {
        return $this->folderId;
    }

    public function getColumns(): array
    {
        return $this->columns;
    }

    public function getFilters(): FilterParameter
    {
        return $this->filters ?? new FilterParameter();
    }
}
