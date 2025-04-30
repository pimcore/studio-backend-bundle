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

namespace Pimcore\Bundle\StudioBackendBundle\MappedParameter\Filter;

use function is_string;

/**
 * @internal
 */
readonly class SimpleColumnFilter
{
    public function __construct(
        private string $type,
        private mixed $filterValue,
    ) {
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getFilterValue(): mixed
    {
        if (is_string($this->filterValue)) {
            return trim($this->filterValue);
        }

        return $this->filterValue;
    }
}
