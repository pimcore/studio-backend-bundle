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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\Listing\Filter;

use Codeception\Test\Unit;
use Doctrine\DBAL\Connection;
use Pimcore\Bundle\StaticResolverBundle\Db\DbResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Filter\MappedParameter\FilterParameter;
use Pimcore\Bundle\StudioBackendBundle\Listing\Filter\DateFilter;
use Pimcore\Model\Element\Note\Listing as NoteListing;

/**
 * @internal
 */
final class DateFilterTest extends Unit
{
    public function testDateFilterOnOperator(): void
    {
        $filter = $this->createFilter();
        $listing = new NoteListing();

        $params = new FilterParameter(columnFilters: [
            ['key' => 'creationDate', 'type' => 'date', 'filterValue' => ['operator' => 'on', 'value' => '2024-06-10']],
        ]);

        $filter->apply($params, $listing);

        $this->assertStringContainsString('`creationDate` BETWEEN :minTime AND :maxTime', $listing->getCondition());
    }

    public function testDateFilterFromOperator(): void
    {
        $filter = $this->createFilter();
        $listing = new NoteListing();

        $params = new FilterParameter(columnFilters: [
            ['key' => 'creationDate', 'type' => 'date', 'filterValue' => ['operator' => 'from', 'value' => '2024-06-10']],
        ]);

        $filter->apply($params, $listing);

        $this->assertStringContainsString('`creationDate` >= :creationDate', $listing->getCondition());
    }

    public function testDateFilterToOperator(): void
    {
        $filter = $this->createFilter();
        $listing = new NoteListing();

        $params = new FilterParameter(columnFilters: [
            ['key' => 'creationDate', 'type' => 'date', 'filterValue' => ['operator' => 'to', 'value' => '2024-06-10']],
        ]);

        $filter->apply($params, $listing);

        $this->assertStringContainsString('`creationDate` <= :creationDate', $listing->getCondition());
    }

    public function testDateFilterEscape(): void
    {
        $filter = $this->createFilter();
        $listing = new NoteListing();

        $injectionKey = 'id` BETWEEN 0 AND 99999999999) AND SLEEP(3)-- ';
        $params = new FilterParameter(columnFilters: [
            ['key' => $injectionKey, 'type' => 'date', 'filterValue' => ['operator' => 'on', 'value' => '2024-01-01']],
        ]);

        $filter->apply($params, $listing);

        $condition = $listing->getCondition();
        $this->assertStringContainsString('`id``', $condition);
        $this->assertStringNotContainsString('`id` BETWEEN 0', $condition);
    }

    public function testDateFilterThrowsWhenFilterValueNotArray(): void
    {
        $filter = $this->createFilter();
        $listing = new NoteListing();

        $params = new FilterParameter(columnFilters: [
            ['key' => 'creationDate', 'type' => 'date', 'filterValue' => 'not-an-array'],
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Filter value for date must be an array');
        $filter->apply($params, $listing);
    }

    private function createFilter(): DateFilter
    {
        $connection = $this->makeEmpty(Connection::class, [
            'quoteIdentifier' => function (string $identifier): string {
                return '`' . str_replace('`', '``', $identifier) . '`';
            },
        ]);
        $dbResolver = $this->makeEmpty(DbResolverInterface::class, [
            'get' => $connection,
        ]);

        return new DateFilter($dbResolver);
    }
}
