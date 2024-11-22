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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\MappedParameter\Filter;

use Codeception\Test\Unit;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\Filter\SimpleColumnFilter;

/**
 * @internal
 */
final class SimpleColumnFilterTest extends Unit
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
