<?php
declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under following license:
 * - Pimcore Commercial License (PCL)
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     PCL
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