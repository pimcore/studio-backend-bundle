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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\DataIndex\Filter\DataObject;

use Codeception\Stub\Expected;
use Codeception\Test\Unit;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Filter\DataObject\RelationFilter;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Query\AssetQueryInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Query\DataObjectQueryInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Grid\Column\ColumnType;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\Filter\ColumnFilter;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\Filter\ColumnFiltersParameterInterface;

/**
 * @internal
 */
final class RelationFilterTest extends Unit
{
    public function testRelationFilterReturnsQueryWhenParametersNotColumnFiltersParameterInterface(): void
    {
        $filter = new RelationFilter();
        $queryMock = $this->makeEmpty(DataObjectQueryInterface::class);

        $result = $filter->apply('invalid', $queryMock);

        $this->assertSame($queryMock, $result);
    }

    public function testRelationFilterReturnsQueryWhenQueryNotDataObjectQueryInterface(): void
    {
        $filter = new RelationFilter();
        $parameterMock = $this->makeEmpty(ColumnFiltersParameterInterface::class);
        $queryMock = $this->makeEmpty(AssetQueryInterface::class);

        $result = $filter->apply($parameterMock, $queryMock);

        $this->assertSame($queryMock, $result);
    }

    public function testRelationFilterThrowsExceptionWhenFilterValueNotArray(): void
    {
        $filter = new RelationFilter();
        $parameterMock = $this->makeEmpty(ColumnFiltersParameterInterface::class, [
            'getColumnFilterByType' => function () {
                return [
                    new ColumnFilter('bodyStyle', ColumnType::DATAOBJECT_RELATION->value, 'invalid'),
                ];
            },
        ]);
        $queryMock = $this->makeEmpty(DataObjectQueryInterface::class);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Value for relation filter must be an array');

        $filter->apply($parameterMock, $queryMock);
    }

    public function testRelationFilterThrowsExceptionWhenTypeIsMissing(): void
    {
        $filter = new RelationFilter();
        $parameterMock = $this->makeEmpty(ColumnFiltersParameterInterface::class, [
            'getColumnFilterByType' => function () {
                return [
                    new ColumnFilter('bodyStyle', ColumnType::DATAOBJECT_RELATION->value, ['ids' => [1, 2]]),
                ];
            },
        ]);
        $queryMock = $this->makeEmpty(DataObjectQueryInterface::class);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Value for relation filter must contain a valid type (asset, object, document)');

        $filter->apply($parameterMock, $queryMock);
    }

    public function testRelationFilterThrowsExceptionWhenTypeIsInvalid(): void
    {
        $filter = new RelationFilter();
        $parameterMock = $this->makeEmpty(ColumnFiltersParameterInterface::class, [
            'getColumnFilterByType' => function () {
                return [
                    new ColumnFilter(
                        'bodyStyle',
                        ColumnType::DATAOBJECT_RELATION->value,
                        ['type' => 'invalid', 'ids' => [1, 2]]
                    ),
                ];
            },
        ]);
        $queryMock = $this->makeEmpty(DataObjectQueryInterface::class);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Value for relation filter must contain a valid type (asset, object, document)');

        $filter->apply($parameterMock, $queryMock);
    }

    public function testRelationFilterThrowsExceptionWhenIdsIsMissing(): void
    {
        $filter = new RelationFilter();
        $parameterMock = $this->makeEmpty(ColumnFiltersParameterInterface::class, [
            'getColumnFilterByType' => function () {
                return [
                    new ColumnFilter('bodyStyle', ColumnType::DATAOBJECT_RELATION->value, ['type' => 'object']),
                ];
            },
        ]);
        $queryMock = $this->makeEmpty(DataObjectQueryInterface::class);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Value for relation filter must contain a non-empty ids array');

        $filter->apply($parameterMock, $queryMock);
    }

    public function testRelationFilterThrowsExceptionWhenIdsIsEmpty(): void
    {
        $filter = new RelationFilter();
        $parameterMock = $this->makeEmpty(ColumnFiltersParameterInterface::class, [
            'getColumnFilterByType' => function () {
                return [
                    new ColumnFilter(
                        'bodyStyle',
                        ColumnType::DATAOBJECT_RELATION->value,
                        ['type' => 'object', 'ids' => []]
                    ),
                ];
            },
        ]);
        $queryMock = $this->makeEmpty(DataObjectQueryInterface::class);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Value for relation filter must contain a non-empty ids array');

        $filter->apply($parameterMock, $queryMock);
    }

    public function testRelationFilterAppliesFilterWithObjectType(): void
    {
        $filter = new RelationFilter();
        $parameterMock = $this->makeEmpty(ColumnFiltersParameterInterface::class, [
            'getColumnFilterByType' => function () {
                return [
                    new ColumnFilter(
                        'bodyStyle',
                        ColumnType::DATAOBJECT_RELATION->value,
                        ['type' => 'object', 'ids' => [6]]
                    ),
                ];
            },
        ]);

        $queryMock = $this->makeEmpty(DataObjectQueryInterface::class, [
            'filterMultiSelect' => Expected::once(function ($fieldKey, $ids) {
                $this->assertSame('bodyStyle.object', $fieldKey);
                $this->assertSame([6], $ids);

                return $this->makeEmpty(DataObjectQueryInterface::class);
            }),
        ]);

        $filter->apply($parameterMock, $queryMock);
    }

    public function testRelationFilterAppliesFilterWithAssetType(): void
    {
        $filter = new RelationFilter();
        $parameterMock = $this->makeEmpty(ColumnFiltersParameterInterface::class, [
            'getColumnFilterByType' => function () {
                return [
                    new ColumnFilter(
                        'images',
                        ColumnType::DATAOBJECT_RELATION->value,
                        ['type' => 'asset', 'ids' => [10, 20]]
                    ),
                ];
            },
        ]);

        $queryMock = $this->makeEmpty(DataObjectQueryInterface::class, [
            'filterMultiSelect' => Expected::once(function ($fieldKey, $ids) {
                $this->assertSame('images.asset', $fieldKey);
                $this->assertSame([10, 20], $ids);

                return $this->makeEmpty(DataObjectQueryInterface::class);
            }),
        ]);

        $filter->apply($parameterMock, $queryMock);
    }

    public function testRelationFilterAppliesFilterWithDocumentType(): void
    {
        $filter = new RelationFilter();
        $parameterMock = $this->makeEmpty(ColumnFiltersParameterInterface::class, [
            'getColumnFilterByType' => function () {
                return [
                    new ColumnFilter(
                        'relatedDocuments',
                        ColumnType::DATAOBJECT_RELATION->value,
                        ['type' => 'document', 'ids' => [5, 15, 25]]
                    ),
                ];
            },
        ]);

        $queryMock = $this->makeEmpty(DataObjectQueryInterface::class, [
            'filterMultiSelect' => Expected::once(function ($fieldKey, $ids) {
                $this->assertSame('relatedDocuments.document', $fieldKey);
                $this->assertSame([5, 15, 25], $ids);

                return $this->makeEmpty(DataObjectQueryInterface::class);
            }),
        ]);

        $filter->apply($parameterMock, $queryMock);
    }
}
