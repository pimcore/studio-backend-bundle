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

/**
 * @internal
 */
final readonly class FiltersParameter
{
    public function __construct(
        private array $columnFilters = [],
        private array $drillDownFilters = [],
    ) {
    }

    public function getColumnFilters(): array
    {
        return $this->columnFilters;
    }

    public function getDrillDownFilters(): array
    {
        return $this->drillDownFilters;
    }
}
