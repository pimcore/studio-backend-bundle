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

use Pimcore\Bundle\GenericDataIndexBundle\Enum\Search\SortDirection;

/**
 * @internal
 */
final readonly class SortFilter
{
    public function __construct(
        private string $key = 'id',
        private string $direction = SortDirection::ASC->value,
    ) {
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function getDirection(): string
    {
        return $this->direction;
    }
}
