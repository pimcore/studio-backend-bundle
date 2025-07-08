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

namespace Pimcore\Bundle\StudioBackendBundle\CustomReport\MappedParameter;

/**
 * @internal
 */
final readonly class DrillDownParameter
{
    public function __construct(
        private string $name,
        private string $field,
        private FiltersParameter $filters = new FiltersParameter(),
    ) {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getField(): string
    {
        return $this->field;
    }

    public function getFilters(): FiltersParameter
    {
        return $this->filters;
    }
}
