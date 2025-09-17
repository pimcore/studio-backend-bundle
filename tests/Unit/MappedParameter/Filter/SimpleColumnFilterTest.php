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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\MappedParameter\Filter;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\Filter\SimpleColumnFilter;

/**
 * @internal
 */
#[CoversClass(SimpleColumnFilter::class)]
final class SimpleColumnFilterTest extends TestCase
{
    public function testTrimFilterValue(): void
    {
        $filter = new SimpleColumnFilter('type', '  value  ');

        $this->assertSame('value', $filter->getFilterValue());
    }

    public function testGetFilterValue(): void
    {
        $filter = new SimpleColumnFilter('type', ['value']);

        $this->assertSame(['value'], $filter->getFilterValue());
    }
}
