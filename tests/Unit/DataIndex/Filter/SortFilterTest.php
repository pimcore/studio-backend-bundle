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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\DataIndex\Filter;

use Codeception\Stub\Expected;
use Codeception\Test\Unit;
use PHPUnit\Framework\Attributes\DataProvider;
use Pimcore\Bundle\GenericDataIndexBundle\Enum\Search\SortDirection;
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\ClassDefinitionResolverInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\Objectbrick\DefinitionResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Filter\SortFilter;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Query\AssetQueryInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Query\DataObjectQueryInterface;
use Pimcore\Bundle\StudioBackendBundle\Filter\MappedParameter\FilterParameter;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\Filter\SortFilter as SortFilterParameter;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\Filter\SortFilterParameterInterface;
use Pimcore\Model\DataObject\ClassDefinition;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\ClassDefinition\Data\Input;
use Pimcore\Model\DataObject\ClassDefinition\Data\InputQuantityValue;
use Pimcore\Model\DataObject\ClassDefinition\Data\Localizedfields;
use Pimcore\Model\DataObject\ClassDefinition\Data\Objectbricks;
use Pimcore\Model\DataObject\ClassDefinition\Data\QuantityValue;
use Pimcore\Model\DataObject\Objectbrick\Definition as ObjectBrickDefinition;

/**
 * @internal
 */
final class SortFilterTest extends Unit
{
    public function testIfParameterIsNotInstanceOfSortFilterParameterInterface(): void
    {
        $sortFilter = $this->createSortFilter();
        $query = $this->makeEmpty(AssetQueryInterface::class, [
            'orderByField' => Expected::never(),
        ]);

        $sortFilter->apply('test', $query);
    }

    public function testSortDirectionWithDesc(): void
    {
        $sortFilter = $this->createSortFilter();
        $sortFilterParam = new SortFilterParameter('key', 'desc');
        $parameter = $this->makeEmpty(SortFilterParameterInterface::class, [
            'getSortFilter' => function () use ($sortFilterParam) {
                return $sortFilterParam;
            },
            'getSortFilters' => function () use ($sortFilterParam) {
                return [$sortFilterParam];
            },
        ]);

        $query = $this->makeEmpty(AssetQueryInterface::class, [
            'orderByField' => Expected::once(function ($key, $direction) {
                $this->assertSame('key', $key);
                $this->assertSame(SortDirection::DESC, $direction);

                return $this->makeEmpty(AssetQueryInterface::class);
            }),
        ]);

        $sortFilter->apply($parameter, $query);
    }

    public function testSortDirectionWithDefaultValue(): void
    {
        $sortFilter = $this->createSortFilter();
        $sortFilterParam = new SortFilterParameter();
        $parameter = $this->makeEmpty(SortFilterParameterInterface::class, [
            'getSortFilter' => function () use ($sortFilterParam) {
                return $sortFilterParam;
            },
            'getSortFilters' => function () use ($sortFilterParam) {
                return [$sortFilterParam];
            },
        ]);

        $query = $this->makeEmpty(AssetQueryInterface::class, [
            'orderByField' => Expected::once(function ($key, $direction) {
                $this->assertSame('id', $key);
                $this->assertSame(SortDirection::ASC, $direction);

                return $this->makeEmpty(AssetQueryInterface::class);
            }),
        ]);

        $sortFilter->apply($parameter, $query);
    }

    public function testMultipleSortFilters(): void
    {
        $sortFilter = $this->createSortFilter();
        $primarySort = new SortFilterParameter('name', 'asc');
        $secondarySort = new SortFilterParameter('id', 'desc');
        $parameter = $this->makeEmpty(SortFilterParameterInterface::class, [
            'getSortFilter' => function () use ($primarySort) {
                return $primarySort;
            },
            'getSortFilters' => function () use ($primarySort, $secondarySort) {
                return [$primarySort, $secondarySort];
            },
        ]);

        $callCount = 0;
        $query = $this->makeEmpty(AssetQueryInterface::class, [
            'orderByField' => function ($key, $direction) use (&$callCount) {
                if ($callCount === 0) {
                    $this->assertSame('name', $key);
                    $this->assertSame(SortDirection::ASC, $direction);
                } elseif ($callCount === 1) {
                    $this->assertSame('id', $key);
                    $this->assertSame(SortDirection::DESC, $direction);
                }
                $callCount++;

                return $this->makeEmpty(AssetQueryInterface::class);
            },
        ]);

        $sortFilter->apply($parameter, $query);
        $this->assertSame(2, $callCount);
    }

    public function testSortByClassnameColumnKeyMapsToIndexField(): void
    {
        $sortFilter = $this->createSortFilter();
        $sortFilterParam = new SortFilterParameter('classname', 'asc');
        $parameter = $this->makeEmpty(SortFilterParameterInterface::class, [
            'getSortFilter' => function () use ($sortFilterParam) {
                return $sortFilterParam;
            },
            'getSortFilters' => function () use ($sortFilterParam) {
                return [$sortFilterParam];
            },
        ]);

        $query = $this->makeEmpty(AssetQueryInterface::class, [
            'orderByField' => Expected::once(function ($key, $direction) {
                $this->assertSame('className', $key);
                $this->assertSame(SortDirection::ASC, $direction);

                return $this->makeEmpty(AssetQueryInterface::class);
            }),
        ]);

        $sortFilter->apply($parameter, $query);
    }

    /**
     * @dataProvider dataObjectSortFieldProvider
     */
    #[DataProvider('dataObjectSortFieldProvider')]
    public function testDataObjectSortFieldDependsOnTheFieldType(
        string $key,
        ?string $locale,
        string $expectedField
    ): void {
        $sortFilter = $this->createSortFilter(
            $this->makeEmpty(ClassDefinitionResolverInterface::class, [
                'getByName' => Expected::once($this->createClassDefinition()),
            ]),
            $this->makeEmpty(DefinitionResolverInterface::class, [
                'getByKey' => $this->createObjectBrickDefinition(),
            ])
        );
        $query = $this->makeEmpty(DataObjectQueryInterface::class, [
            'orderByField' => Expected::once(function ($field, $direction) use ($expectedField) {
                $this->assertSame($expectedField, $field);
                $this->assertSame(SortDirection::DESC, $direction);

                return $this->makeEmpty(DataObjectQueryInterface::class);
            }),
        ]);

        $sortFilter->apply($this->createFilterParameter($key, $locale, 'Car'), $query);
    }

    public static function dataObjectSortFieldProvider(): array
    {
        return [
            'quantity value' => ['price', null, 'price.value'],
            'localized quantity value' => ['weight', 'en', 'weight.en.value'],
            'quantity value in an object brick' => ['bricks.Engine.power', null, 'bricks.Engine.power.value'],
            'localized quantity value in an object brick' => [
                'bricks.Engine.torque',
                'de',
                'bricks.Engine.torque.de.value',
            ],
            'input quantity value' => ['height', null, 'height'],
            'input' => ['name', null, 'name'],
            'object brick not allowed in the field' => ['bricks.Wheel.power', null, 'bricks.Wheel.power'],
            'system column' => ['classname', null, 'className'],
        ];
    }

    public function testDataObjectSortWithOnlyAClassIdUsesTheClassDefinition(): void
    {
        $sortFilter = $this->createSortFilter(
            $this->makeEmpty(ClassDefinitionResolverInterface::class, [
                'getByName' => Expected::never(),
                'getById' => Expected::once(function (string $id) {
                    $this->assertSame('CAR', $id);

                    return $this->createClassDefinition();
                }),
            ])
        );
        $query = $this->makeEmpty(DataObjectQueryInterface::class, [
            'orderByField' => Expected::once(function ($field) {
                $this->assertSame('price.value', $field);

                return $this->makeEmpty(DataObjectQueryInterface::class);
            }),
        ]);
        $parameter = $this->createFilterParameter('price', null, null);
        $parameter->setClassId('CAR');

        $sortFilter->apply($parameter, $query);
    }

    public function testDataObjectSortWithoutClassNameOrIdKeepsTheKey(): void
    {
        $sortFilter = $this->createSortFilter(
            $this->makeEmpty(ClassDefinitionResolverInterface::class, [
                'getByName' => Expected::never(),
                'getById' => Expected::never(),
            ])
        );
        $query = $this->makeEmpty(DataObjectQueryInterface::class, [
            'orderByField' => Expected::once(function ($field) {
                $this->assertSame('price', $field);

                return $this->makeEmpty(DataObjectQueryInterface::class);
            }),
        ]);

        $sortFilter->apply($this->createFilterParameter('price', null, null), $query);
    }

    public function testAssetSortDoesNotLoadAClassDefinition(): void
    {
        $sortFilter = $this->createSortFilter(
            $this->makeEmpty(ClassDefinitionResolverInterface::class, [
                'getByName' => Expected::never(),
            ])
        );
        $query = $this->makeEmpty(AssetQueryInterface::class, [
            'orderByField' => Expected::once(function ($field) {
                $this->assertSame('price', $field);

                return $this->makeEmpty(AssetQueryInterface::class);
            }),
        ]);

        $sortFilter->apply($this->createFilterParameter('price', null, 'Car'), $query);
    }

    private function createSortFilter(
        ?ClassDefinitionResolverInterface $classDefinitionResolver = null,
        ?DefinitionResolverInterface $objectBrickDefinitionResolver = null
    ): SortFilter {
        return new SortFilter(
            $classDefinitionResolver ?? $this->makeEmpty(ClassDefinitionResolverInterface::class),
            $objectBrickDefinitionResolver ?? $this->makeEmpty(DefinitionResolverInterface::class)
        );
    }

    private function createFilterParameter(string $key, ?string $locale, ?string $className): FilterParameter
    {
        $parameter = new FilterParameter(sortFilter: new SortFilterParameter($key, 'desc', $locale));
        $parameter->setClassName($className);

        return $parameter;
    }

    private function createClassDefinition(): ClassDefinition
    {
        $bricks = $this->createField(new Objectbricks(), 'bricks');
        $bricks->setAllowedTypes(['Engine']);

        $classDefinition = new ClassDefinition();
        $classDefinition->addFieldDefinition('price', $this->createField(new QuantityValue(), 'price'));
        $classDefinition->addFieldDefinition('height', $this->createField(new InputQuantityValue(), 'height'));
        $classDefinition->addFieldDefinition('name', $this->createField(new Input(), 'name'));
        $classDefinition->addFieldDefinition('bricks', $bricks);
        $classDefinition->addFieldDefinition('localizedfields', $this->createLocalizedFields('weight'));

        return $classDefinition;
    }

    private function createObjectBrickDefinition(): ObjectBrickDefinition
    {
        $power = $this->createField(new QuantityValue(), 'power');
        $localizedFields = $this->createLocalizedFields('torque');

        return $this->makeEmpty(ObjectBrickDefinition::class, [
            'getFieldDefinition' => static fn (string $key) => match ($key) {
                'power' => $power,
                'localizedfields' => $localizedFields,
                default => null,
            },
        ]);
    }

    private function createLocalizedFields(string $quantityValueName): Localizedfields
    {
        $localizedFields = new Localizedfields();
        $localizedFields->setChildren([$this->createField(new QuantityValue(), $quantityValueName)]);

        return $localizedFields;
    }

    /**
     * @template T of Data
     *
     * @param T $field
     *
     * @return T
     */
    private function createField(Data $field, string $name): Data
    {
        $field->setName($name);

        return $field;
    }
}
