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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\DataObject\Data\Adapter;

use Codeception\Test\Unit;
use Exception;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\Adapter\NumericRangeAdapter;
use Pimcore\Model\DataObject\ClassDefinition\Data\NumericRange as NumericRangeDefinition;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\DataObject\Data\NumericRange;
use Pimcore\Model\UserInterface;

/**
 * @internal
 *
 * @see https://github.com/pimcore/pimcore/issues/18144
 */
final class NumericRangeAdapterTest extends Unit
{
    /**
     * @throws Exception
     */
    public function testGetDataForSetterWithBothBounds(): void
    {
        $result = $this->callAdapter(['range' => ['minimum' => 5, 'maximum' => 10]]);

        $this->assertInstanceOf(NumericRange::class, $result);
        $this->assertSame(5, $result->getMinimum());
        $this->assertSame(10, $result->getMaximum());
    }

    /**
     * Regression test for #18144: a range with only the minimum set must be persisted.
     *
     * @throws Exception
     */
    public function testGetDataForSetterWithMinimumOnly(): void
    {
        $result = $this->callAdapter(['range' => ['minimum' => 5, 'maximum' => null]]);

        $this->assertInstanceOf(NumericRange::class, $result);
        $this->assertSame(5, $result->getMinimum());
        $this->assertNull($result->getMaximum());
    }

    /**
     * Regression test for #18144: a range with only the maximum set must be persisted.
     *
     * @throws Exception
     */
    public function testGetDataForSetterWithMaximumOnly(): void
    {
        $result = $this->callAdapter(['range' => ['maximum' => 10]]);

        $this->assertInstanceOf(NumericRange::class, $result);
        $this->assertNull($result->getMinimum());
        $this->assertSame(10, $result->getMaximum());
    }

    /**
     * @throws Exception
     */
    public function testGetDataForSetterKeepsZeroBound(): void
    {
        $result = $this->callAdapter(['range' => ['minimum' => 0, 'maximum' => null]]);

        $this->assertInstanceOf(NumericRange::class, $result);
        $this->assertSame(0, $result->getMinimum());
        $this->assertNull($result->getMaximum());
    }

    /**
     * @throws Exception
     */
    public function testGetDataForSetterReturnsNullWhenBothBoundsNull(): void
    {
        $this->assertNull($this->callAdapter(['range' => ['minimum' => null, 'maximum' => null]]));
    }

    /**
     * @throws Exception
     */
    public function testGetDataForSetterReturnsNullWhenValueIsNotAnArray(): void
    {
        $this->assertNull($this->callAdapter(['range' => null]));
    }

    /**
     * @throws Exception
     */
    private function callAdapter(array $data): ?NumericRange
    {
        $adapter = new NumericRangeAdapter();

        return $adapter->getDataForSetter(
            $this->makeEmpty(Concrete::class),
            $this->makeEmpty(NumericRangeDefinition::class),
            'range',
            $data,
            $this->makeEmpty(UserInterface::class)
        );
    }
}
