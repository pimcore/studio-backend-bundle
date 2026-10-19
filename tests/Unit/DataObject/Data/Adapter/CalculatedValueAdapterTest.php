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
use Pimcore\Bundle\StudioBackendBundle\DataObject\Service\CalculatedValueSnapshotServiceInterface;
use Pimcore\Model\DataObject\ClassDefinition\Data\CalculatedValue as CalculatedValueDefinition;
use Pimcore\Model\DataObject\ClassDefinition\Data\Input;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\DataObject\Data\CalculatedValue;
use Pimcore\Model\DataObject\Objectbrick\Data\AbstractData;
use Stringable;
use function array_key_exists;
use function implode;

/**
 * @internal
 */
final class CalculatedValueAdapterTest extends Unit
{
    /** @var array<int, CalculatedValue> */
    private array $editModeCalls = [];

    /** @var array<int, CalculatedValue> */
    private array $storedValueLookups = [];

    public function testEditViewComputesTheValueFreshly(): void
    {
        $adapter = $this->createAdapter(editModeValue: 'live', storedValues: ['object~~~~total' => 'stored']);

        $result = $adapter->getDetailData($this->makeEmpty(Concrete::class), null, $this->definition('total'));

        $this->assertSame('live', $result);
        $this->assertCount(1, $this->editModeCalls);
        $this->assertCount(0, $this->storedValueLookups, 'the edit view must never read what a version stores');
        $this->assertSame('object', $this->editModeCalls[0]->getOwnerType());
        $this->assertSame('total', $this->editModeCalls[0]->getFieldname());
    }

    public function testHistoricalVersionShowsTheValueTheVersionStores(): void
    {
        $adapter = $this->createAdapter(editModeValue: 'live', storedValues: ['object~~~~total' => 'stored']);

        $result = $adapter->getDetailData(
            $this->makeEmpty(Concrete::class),
            null,
            $this->definition('total'),
            new FieldContextData(historicalVersion: true)
        );

        $this->assertSame('stored', $result);
        $this->assertCount(0, $this->editModeCalls, 'a stored value must not be recomputed for the edit view');
        $this->assertCount(1, $this->storedValueLookups);
        // a version stores object-level values under the context of the generated getters: no owner name
        $this->assertSame('object', $this->storedValueLookups[0]->getOwnerType());
        $this->assertNull($this->storedValueLookups[0]->getOwnerName());
        $this->assertNull($this->storedValueLookups[0]->getIndex());
        $this->assertNull($this->storedValueLookups[0]->getPosition());
        $this->assertSame('total', $this->storedValueLookups[0]->getFieldname());
    }

    public function testHistoricalVersionWithoutStoredValueIsShownLikeTheEditView(): void
    {
        // versions created before the option, auto-save versions, fields of containers
        $adapter = $this->createAdapter(editModeValue: '10 EUR', storedValues: []);

        $result = $adapter->getDetailData(
            $this->makeEmpty(Concrete::class),
            null,
            $this->definition('total'),
            new FieldContextData(historicalVersion: true)
        );

        $this->assertSame('10 EUR', $result);
        $this->assertCount(1, $this->storedValueLookups);
        $this->assertCount(1, $this->editModeCalls, 'without a stored value the edit mode formatting must be kept');
        $this->assertSame('total', $this->editModeCalls[0]->getOwnerName(), 'the edit view inputs must not change');
    }

    public function testHistoricalVersionStoringNullShowsNothingInsteadOfRecomputing(): void
    {
        $adapter = $this->createAdapter(editModeValue: 'live', storedValues: ['object~~~~total' => null]);

        $result = $adapter->getDetailData(
            $this->makeEmpty(Concrete::class),
            null,
            $this->definition('total'),
            new FieldContextData(historicalVersion: true)
        );

        $this->assertNull($result);
        $this->assertCount(0, $this->editModeCalls);
    }

    public function testHistoricalVersionKeepsTheLocalizedFieldContext(): void
    {
        $adapter = $this->createAdapter(
            editModeValue: 'live',
            storedValues: ['localizedfield~localizedfields~~de~total' => 'gespeichert']
        );

        $result = $adapter->getDetailData(
            $this->makeEmpty(Concrete::class),
            null,
            $this->definition('total'),
            new FieldContextData(language: 'de', historicalVersion: true)
        );

        $this->assertSame('gespeichert', $result);
        $this->assertCount(1, $this->storedValueLookups);
        // the stored values of localized calculated fields are keyed by owner type and language
        $this->assertSame('localizedfield', $this->storedValueLookups[0]->getOwnerType());
        $this->assertSame('localizedfields', $this->storedValueLookups[0]->getOwnerName());
        $this->assertSame('de', $this->storedValueLookups[0]->getPosition());
    }

    public function testHistoricalVersionInsideAnObjectBrickIsShownLikeTheEditView(): void
    {
        $adapter = $this->createAdapter(editModeValue: 'live', storedValues: ['object~~~~total' => 'stored']);
        $brick = $this->makeEmpty(AbstractData::class, ['getFieldname' => 'bricks', 'getType' => 'pricing']);

        $result = $adapter->getDetailData(
            $this->makeEmpty(Concrete::class),
            null,
            $this->definition('total'),
            new FieldContextData(contextObject: $brick, historicalVersion: true)
        );

        // a version only stores object-level and localized fields of the object itself
        $this->assertSame('live', $result);
        $this->assertCount(1, $this->storedValueLookups);
        $this->assertSame('objectbrick', $this->storedValueLookups[0]->getOwnerType());
        $this->assertCount(1, $this->editModeCalls);
    }

    /**
     * @dataProvider storedValueProvider
     */
    public function testStoredValuesAreShownTheWayTheEditViewShowsThem(mixed $storedValue, ?string $expected): void
    {
        $adapter = $this->createAdapter(editModeValue: 'live', storedValues: ['object~~~~total' => $storedValue]);

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
            // the core stores any array of scalars; parts JSON cannot represent are substituted, never fail the detail
            'array with INF' => [['total' => INF], '{"total":0}'],
            'array with NAN' => [['total' => NAN], '{"total":0}'],
            'array with invalid UTF-8' => [['name' => "\xB1\x31"], '{"name":"\ufffd1"}'],
        ];
    }

    public function testOtherFieldDefinitionsAreIgnored(): void
    {
        $adapter = $this->createAdapter(editModeValue: 'live', storedValues: ['object~~~~total' => 'stored']);

        $result = $adapter->getDetailData(
            $this->makeEmpty(Concrete::class),
            'raw',
            $this->makeEmpty(Input::class),
            new FieldContextData(historicalVersion: true)
        );

        $this->assertNull($result);
        $this->assertCount(0, $this->editModeCalls);
        $this->assertCount(0, $this->storedValueLookups);
    }

    /**
     * @param array<string, mixed> $storedValues keyed like the core keys a version's stored values:
     *                                           ownerType~ownerName~index~position~fieldname
     */
    private function createAdapter(?string $editModeValue, array $storedValues): CalculatedValueAdapter
    {
        $this->editModeCalls = [];
        $this->storedValueLookups = [];

        $key = static fn (CalculatedValue $context): string => implode('~', [
            $context->getOwnerType(),
            (string) $context->getOwnerName(),
            (string) $context->getIndex(),
            (string) $context->getPosition(),
            $context->getFieldname(),
        ]);

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
            ]),
            $this->makeEmpty(CalculatedValueSnapshotServiceInterface::class, [
                'hasStoredValue' => function (Concrete $object, CalculatedValue $context) use ($storedValues, $key): bool {
                    $this->storedValueLookups[] = $context;

                    return array_key_exists($key($context), $storedValues);
                },
                'getStoredValue' => static fn (Concrete $object, CalculatedValue $context): mixed
                    => $storedValues[$key($context)] ?? null,
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
