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
use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\Adapter\InputQuantityValueAdapter;
use Pimcore\Model\DataObject\ClassDefinition\Data\InputQuantityValue as InputQuantityValueDefinition;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\DataObject\Data\InputQuantityValue;
use Pimcore\Model\UserInterface;

/**
 * @internal
 *
 * @see https://github.com/pimcore/pimcore/issues/18144
 */
final class InputQuantityValueAdapterTest extends Unit
{
    /**
     * @throws Exception
     */
    public function testGetDataForSetterWithValue(): void
    {
        $result = $this->callAdapter(['quantity' => ['value' => 'abc', 'unitId' => null]]);

        $this->assertInstanceOf(InputQuantityValue::class, $result);
        $this->assertSame('abc', $result->getValue());
    }

    /**
     * Regression test for #18144: the string "0" is a valid input value and must be persisted.
     *
     * @throws Exception
     */
    public function testGetDataForSetterKeepsZeroStringValue(): void
    {
        $result = $this->callAdapter(['quantity' => ['value' => '0', 'unitId' => null]]);

        $this->assertInstanceOf(InputQuantityValue::class, $result);
        $this->assertSame('0', $result->getValue());
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
    private function callAdapter(array $data): ?InputQuantityValue
    {
        return (new InputQuantityValueAdapter())->getDataForSetter(
            $this->makeEmpty(Concrete::class),
            $this->makeEmpty(InputQuantityValueDefinition::class),
            'quantity',
            $data,
            $this->makeEmpty(UserInterface::class)
        );
    }
}
