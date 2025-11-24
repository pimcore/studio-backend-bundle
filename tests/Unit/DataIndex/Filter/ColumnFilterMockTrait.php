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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\DataIndex\Filter;

use Pimcore\Bundle\StudioBackendBundle\MappedParameter\Filter\ColumnFilter;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\Filter\ColumnFiltersParameterInterface;

/**
 * @internal
 */
trait ColumnFilterMockTrait
{
    public function getColumnFilterMock(string $key, string $type, mixed $value): ColumnFiltersParameterInterface
    {
        return $this->makeEmpty(ColumnFiltersParameterInterface::class, [
            'getColumnFilterByType' => function () use ($key, $type, $value) {
                return [
                    new ColumnFilter($key, $type, $value),
                ];
            },
        ]);
    }
}
