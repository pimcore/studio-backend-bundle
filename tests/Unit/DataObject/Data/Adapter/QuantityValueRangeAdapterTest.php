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
use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\Adapter\QuantityValueRangeAdapter;
use Pimcore\Model\DataObject\ClassDefinition\Data\QuantityValueRange as QuantityValueRangeDefinition;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\DataObject\Data\QuantityValueRange;
use Pimcore\Model\UserInterface;

/**
 * @internal
 *
 * @see https://github.com/pimcore/pimcore/issues/18144
 */
final class QuantityValueRangeAdapterTest extends Unit
{
    /**
     * @throws Exception
     */
    public function testGetDataForSetterWithBothBounds(): void
    {
        $result = $this->callAdapter(['range' => ['minimum' => 5, 'maximum' => 10, 'unitId' => null]]);

        $this->assertInstanceOf(QuantityValueRange::class, $result);
        $this->assertSame(5, $result->getMinimum());
        $this->assertSame(10, $result->getMaximum());
    }

    /**
     * Regression test for #18144: a single bound (incl. 0) must be persisted.
     *
     * @throws Exception
     */
    public function testGetDataForSetterKeepsZeroMinimumOnly(): void
    {
        $result = $this->callAdapter(['range' => ['minimum' => 0, 'maximum' => null, 'unitId' => null]]);

        $this->assertInstanceOf(QuantityValueRange::class, $result);
        $this->assertSame(0, $result->getMinimum());
        $this->assertNull($result->getMaximum());
    }

    /**
     * @throws Exception
     */
    public function testGetDataForSetterWithMaximumOnly(): void
    {
        $result = $this->callAdapter(['range' => ['minimum' => null, 'maximum' => 10, 'unitId' => null]]);

        $this->assertInstanceOf(QuantityValueRange::class, $result);
        $this->assertNull($result->getMinimum());
        $this->assertSame(10, $result->getMaximum());
    }

    /**
     * @throws Exception
     */
    public function testGetDataForSetterReturnsNullWhenBothBoundsNull(): void
    {
        $this->assertNull($this->callAdapter(['range' => ['minimum' => null, 'maximum' => null, 'unitId' => null]]));
    }

    /**
     * @throws Exception
     */
    private function callAdapter(array $data): ?QuantityValueRange
    {
        return (new QuantityValueRangeAdapter())->getDataForSetter(
            $this->makeEmpty(Concrete::class),
            $this->makeEmpty(QuantityValueRangeDefinition::class),
            'range',
            $data,
            $this->makeEmpty(UserInterface::class)
        );
    }
}
