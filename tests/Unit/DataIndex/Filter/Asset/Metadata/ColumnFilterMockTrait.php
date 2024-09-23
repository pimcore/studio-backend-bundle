<?php
declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\DataIndex\Filter\Asset\Metadata;

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
