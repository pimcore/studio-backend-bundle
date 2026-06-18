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
use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\Adapter\QuantityValueAdapter;
use Pimcore\Model\DataObject\ClassDefinition\Data\QuantityValue as QuantityValueDefinition;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\DataObject\Data\QuantityValue;
use Pimcore\Model\UserInterface;

/**
 * @internal
 *
 * @see https://github.com/pimcore/pimcore/issues/18144
 */
final class QuantityValueAdapterTest extends Unit
{
    /**
     * @throws Exception
     */
    public function testGetDataForSetterWithValue(): void
    {
        $result = $this->callAdapter(['quantity' => ['value' => 5, 'unitId' => null]]);

        $this->assertInstanceOf(QuantityValue::class, $result);
        $this->assertSame(5, $result->getValue());
    }

    /**
     * Regression test for #18144: a value of 0 must be persisted, not treated as empty.
     *
     * @throws Exception
     */
    public function testGetDataForSetterKeepsZeroValue(): void
    {
        $result = $this->callAdapter(['quantity' => ['value' => 0, 'unitId' => null]]);

        $this->assertInstanceOf(QuantityValue::class, $result);
        $this->assertSame(0, $result->getValue());
    }

    /**
     * @throws Exception
     */
    public function testGetDataForSetterReturnsNullWhenValueNull(): void
    {
        $this->assertNull($this->callAdapter(['quantity' => ['value' => null, 'unitId' => null]]));
    }

    /**
     * @throws Exception
     */
    public function testGetDataForSetterReturnsNullWhenValueEmptyString(): void
    {
        $this->assertNull($this->callAdapter(['quantity' => ['value' => '', 'unitId' => null]]));
    }

    /**
     * @throws Exception
     */
    private function callAdapter(array $data): ?QuantityValue
    {
        return (new QuantityValueAdapter())->getDataForSetter(
            $this->makeEmpty(Concrete::class),
            $this->makeEmpty(QuantityValueDefinition::class),
            'quantity',
            $data,
            $this->makeEmpty(UserInterface::class)
        );
    }
}
