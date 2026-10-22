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
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Filter\EmptyFilter;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Query\QueryInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Filter\FilterOperator;
use Pimcore\Bundle\StudioBackendBundle\Grid\Column\ColumnType;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\Filter\ColumnFilter;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\Filter\ColumnFiltersParameterInterface;

/**
 * @internal
 */
final class EmptyFilterTest extends Unit
{
    public function testQueryIsReturnedUntouchedWhenParametersAreNotColumnFilters(): void
    {
        $query = $this->makeEmpty(QueryInterface::class, [
            'filterByPql' => Expected::never(),
        ]);

        $this->assertSame($query, (new EmptyFilter())->apply('invalid', $query));
    }

    public function testQueryIsReturnedUntouchedWithoutOperatorFilters(): void
    {
        $query = $this->makeEmpty(QueryInterface::class, [
            'filterByPql' => Expected::never(),
        ]);

        $this->assertSame($query, (new EmptyFilter())->apply($this->getParameters(), $query));
    }

    /**
     * @dataProvider pqlProvider
     */
    public function testOperatorIsTranslatedToPql(
        string $key,
        string $type,
        FilterOperator $operator,
        string $expectedPql,
        ?string $locale = null
    ): void {
        $parameters = $this->getParameters(new ColumnFilter($key, $type, null, $locale, $operator));

        $filteredQuery = $this->makeEmpty(QueryInterface::class);
        $query = $this->makeEmpty(QueryInterface::class, [
            'filterByPql' => Expected::once(
                function (string $pql) use ($expectedPql, $filteredQuery) {
                    $this->assertSame($expectedPql, $pql);

                    return $filteredQuery;
                }
            ),
        ]);

        $this->assertSame($filteredQuery, (new EmptyFilter())->apply($parameters, $query));
    }

    public function pqlProvider(): array
    {
        return [
            'text field is empty' => [
                'sku', ColumnType::SYSTEM_STRING->value, FilterOperator::EMPTY, 'sku = EMPTY',
            ],
            'text field has a value' => [
                'sku', ColumnType::SYSTEM_STRING->value, FilterOperator::NOT_EMPTY, 'sku != EMPTY',
            ],
            'select field is empty' => [
                'color', ColumnType::SYSTEM_SELECT->value, FilterOperator::EMPTY, 'color = EMPTY',
            ],
            'localized text field uses the locale' => [
                'description', ColumnType::SYSTEM_STRING->value, FilterOperator::EMPTY, 'description.en = EMPTY', 'en',
            ],
            'number field is empty' => [
                'productionYear', ColumnType::SYSTEM_NUMBER->value, FilterOperator::EMPTY, 'productionYear = NULL',
            ],
            'number field has a value' => [
                'productionYear', ColumnType::SYSTEM_NUMBER->value, FilterOperator::NOT_EMPTY, 'productionYear != NULL',
            ],
            'relation is empty' => [
                'manufacturer', ColumnType::DATAOBJECT_RELATION->value, FilterOperator::EMPTY, 'manufacturer = NULL',
            ],
            'relation has a value' => [
                'categories', ColumnType::DATAOBJECT_RELATION->value, FilterOperator::NOT_EMPTY, 'categories != NULL',
            ],
            'unknown type falls back to a null check' => [
                'gallery', 'dataobject.adapter', FilterOperator::EMPTY, 'gallery = NULL',
            ],
            'nested brick field' => [
                'attributes.Engine.power', ColumnType::SYSTEM_QUANTITY_VALUE->value, FilterOperator::EMPTY,
                'attributes.Engine.power = NULL',
            ],
            'file name of an asset is its key' => [
                'filename', ColumnType::SYSTEM_STRING->value, FilterOperator::EMPTY, 'key = EMPTY',
            ],
            'core class name key is mapped to the index field' => [
                'classname', ColumnType::SYSTEM_STRING->value, FilterOperator::NOT_EMPTY, 'className != EMPTY',
            ],
        ];
    }

    public function testEveryOperatorFilterIsApplied(): void
    {
        $parameters = $this->getParameters(
            new ColumnFilter('sku', ColumnType::SYSTEM_STRING->value, null, null, FilterOperator::EMPTY),
            new ColumnFilter('category', ColumnType::DATAOBJECT_RELATION->value, null, null, FilterOperator::NOT_EMPTY),
        );

        $applied = [];
        $query = $this->makeEmpty(QueryInterface::class);
        $query = $this->makeEmpty(QueryInterface::class, [
            'filterByPql' => function (string $pql) use (&$applied, &$query) {
                $applied[] = $pql;

                return $query;
            },
        ]);

        (new EmptyFilter())->apply($parameters, $query);

        $this->assertSame(['sku = EMPTY', 'category != NULL'], $applied);
    }

    /**
     * @dataProvider invalidKeyProvider
     */
    public function testInvalidFieldKeyIsRejected(string $key): void
    {
        $parameters = $this->getParameters(
            new ColumnFilter($key, ColumnType::SYSTEM_STRING->value, null, null, FilterOperator::EMPTY)
        );
        $query = $this->makeEmpty(QueryInterface::class, [
            'filterByPql' => Expected::never(),
        ]);

        $this->expectException(InvalidArgumentException::class);

        (new EmptyFilter())->apply($parameters, $query);
    }

    public function invalidKeyProvider(): array
    {
        return [
            'pql injection' => ['sku = NULL OR id != NULL'],
            'quotes' => ['sku"'],
            'brackets' => ['(sku)'],
            'relation sub query' => ['category:Category.name'],
            'empty key' => [''],
            'leading dot' => ['.sku'],
            'trailing dot' => ['sku.'],
            'leading digit' => ['1sku'],
            'whitespace' => ['sku name'],
        ];
    }

    private function getParameters(ColumnFilter ...$operatorFilters): ColumnFiltersParameterInterface
    {
        return $this->makeEmpty(ColumnFiltersParameterInterface::class, [
            'getOperatorColumnFilters' => static fn () => $operatorFilters,
        ]);
    }
}
