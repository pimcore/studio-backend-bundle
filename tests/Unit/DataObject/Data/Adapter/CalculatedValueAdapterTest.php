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
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\DataObjectServiceResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\Adapter\CalculatedValueAdapter;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\Model\FieldContextData;
use Pimcore\Model\DataObject\ClassDefinition\Data\CalculatedValue as CalculatedValueDefinition;
use Pimcore\Model\DataObject\ClassDefinition\Data\Input;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\DataObject\Data\CalculatedValue;
use Stringable;

/**
 * @internal
 */
final class CalculatedValueAdapterTest extends Unit
{
    /** @var array<int, CalculatedValue> */
    private array $editModeCalls = [];

    /** @var array<int, CalculatedValue> */
    private array $storedValueCalls = [];

    public function testEditViewComputesTheValueFreshly(): void
    {
        $adapter = $this->createAdapter(editModeValue: 'live', storedValue: 'stored');

        $result = $adapter->getDetailData($this->makeEmpty(Concrete::class), null, $this->definition('total'));

        $this->assertSame('live', $result);
        $this->assertCount(1, $this->editModeCalls);
        $this->assertCount(0, $this->storedValueCalls, 'the edit view must never read what a version stores');
        $this->assertSame('object', $this->editModeCalls[0]->getOwnerType());
        $this->assertSame('total', $this->editModeCalls[0]->getFieldname());
    }

    public function testHistoricalVersionShowsTheValueTheVersionStores(): void
    {
        $adapter = $this->createAdapter(editModeValue: 'live', storedValue: 'stored');

        $result = $adapter->getDetailData(
            $this->makeEmpty(Concrete::class),
            null,
            $this->definition('total'),
            new FieldContextData(historicalVersion: true)
        );

        $this->assertSame('stored', $result);
        $this->assertCount(0, $this->editModeCalls, 'a version detail must not recompute the value for the edit view');
        $this->assertCount(1, $this->storedValueCalls);
        $this->assertSame('object', $this->storedValueCalls[0]->getOwnerType());
        $this->assertSame('total', $this->storedValueCalls[0]->getFieldname());
    }

    public function testHistoricalVersionKeepsTheLocalizedFieldContext(): void
    {
        $adapter = $this->createAdapter(editModeValue: 'live', storedValue: 'gespeichert');

        $result = $adapter->getDetailData(
            $this->makeEmpty(Concrete::class),
            null,
            $this->definition('total'),
            new FieldContextData(language: 'de', historicalVersion: true)
        );

        $this->assertSame('gespeichert', $result);
        $this->assertCount(1, $this->storedValueCalls);
        // the stored values of localized calculated fields are keyed by owner type and language
        $this->assertSame('localizedfield', $this->storedValueCalls[0]->getOwnerType());
        $this->assertSame('localizedfields', $this->storedValueCalls[0]->getOwnerName());
        $this->assertSame('de', $this->storedValueCalls[0]->getPosition());
    }

    /**
     * @dataProvider storedValueProvider
     */
    public function testStoredValuesAreShownTheWayTheEditViewShowsThem(mixed $storedValue, ?string $expected): void
    {
        $adapter = $this->createAdapter(editModeValue: 'live', storedValue: $storedValue);

        $result = $adapter->getDetailData(
            $this->makeEmpty(Concrete::class),
            null,
            $this->definition('total'),
            new FieldContextData(historicalVersion: true)
        );

        $this->assertSame($expected, $result);
    }

    public function storedValueProvider(): array
    {
        return [
            'nothing stored' => [null, null],
            'string' => ['42 EUR', '42 EUR'],
            'float' => [12.5, '12.5'],
            'bool' => [true, '1'],
            'stringable' => [
                new class() implements Stringable {
                    public function __toString(): string
                    {
                        return 'stringable';
                    }
                },
                'stringable',
            ],
            'array' => [['net' => 10, 'gross' => 12], '{"net":10,"gross":12}'],
        ];
    }

    public function testOtherFieldDefinitionsAreIgnored(): void
    {
        $adapter = $this->createAdapter(editModeValue: 'live', storedValue: 'stored');

        $result = $adapter->getDetailData(
            $this->makeEmpty(Concrete::class),
            'raw',
            $this->makeEmpty(Input::class),
            new FieldContextData(historicalVersion: true)
        );

        $this->assertNull($result);
        $this->assertCount(0, $this->editModeCalls);
        $this->assertCount(0, $this->storedValueCalls);
    }

    private function createAdapter(?string $editModeValue, mixed $storedValue): CalculatedValueAdapter
    {
        $this->editModeCalls = [];
        $this->storedValueCalls = [];

        return new CalculatedValueAdapter(
            $this->makeEmpty(DataObjectServiceResolverInterface::class, [
                'getCalculatedFieldValueForEditMode' => function (
                    Concrete $object,
                    array $params,
                    ?CalculatedValue $data
                ) use ($editModeValue): ?string {
                    $this->editModeCalls[] = $data;

                    return $editModeValue;
                },
                'getCalculatedFieldValue' => function (mixed $object, ?CalculatedValue $data) use ($storedValue): mixed {
                    $this->storedValueCalls[] = $data;

                    return $storedValue;
                },
            ])
        );
    }

    private function definition(string $name): CalculatedValueDefinition
    {
        $definition = new CalculatedValueDefinition();
        $definition->setName($name);

        return $definition;
    }
}
