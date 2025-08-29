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

/**
 * @internal
 */
final readonly class ColumnFilter extends SimpleColumnFilter
{
    public function __construct(
        private string $key,
        private string $type,
        private mixed $filterValue,
        private ?string $locale = null
    ) {
        parent::__construct($this->type, $this->filterValue);
    }

    public function getKey(): string
    {
        if ($this->locale) {
            return $this->key . '.' . $this->locale;
        }

        return $this->key;
    }
}
