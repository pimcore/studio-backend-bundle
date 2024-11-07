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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\DataIndex\Filter;

use Codeception\Stub\Expected;
use Codeception\Test\Unit;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Filter\FullTextFilter;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Query\AssetQueryInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Query\QueryInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\Filter\SimpleColumnFilter;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\Filter\SimpleColumnFiltersParameterInterface;

/**
 * @internal
 */
final class FullTextFilterTest extends Unit
{
    public function testIsExceptionThrownWhenFilterIsNotAString(): void
    {
        $columnFilter = new SimpleColumnFilter('system.fulltext', 1);
        $parameter = $this->makeEmpty(SimpleColumnFiltersParameterInterface::class, [
            'getSimpleColumnFilterByType' => $columnFilter,
        ]);


        $query = $this->makeEmpty(QueryInterface::class);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Filter value for the fulltext filter must be a string');

        $filter = new FullTextFilter();
        $filter->apply($parameter, $query);
    }

    public function testIfFilterFullTextIsCalled(): void
    {
        $columnFilter = new SimpleColumnFilter('system.fulltext', "term");
        $parameter = $this->makeEmpty(SimpleColumnFiltersParameterInterface::class, [
            'getSimpleColumnFilterByType' => $columnFilter,
        ]);


        $query = $this->makeEmpty(QueryInterface::class, [
            'filterFullText' => Expected::once(function ($term) {
                $this->assertSame("term", $term);

                return $this->makeEmpty(QueryInterface::class);
            }),
        ]);

        $filter = new FullTextFilter();
        $filter->apply($parameter, $query);
    }
}