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
use Pimcore\Bundle\StudioBackendBundle\Filter\MappedParameter\FilterParameter;
use Pimcore\Bundle\StudioBackendBundle\Listing\Filter\EqualsFilter;
use Pimcore\Model\Element\Note\Listing as NoteListing;

/**
 * @internal
 */
final class EqualsFilterTest extends Unit
{
    public function testEqualsFilterAppliesCondition(): void
    {
        $filter = $this->createFilter();
        $listing = new NoteListing();

        $params = new FilterParameter(columnFilters: [
            ['key' => 'type', 'type' => 'equals', 'filterValue' => 'asset'],
        ]);

        $filter->apply($params, $listing);

        $this->assertSame('(`type` = :type) ', $listing->getCondition());
        $this->assertSame(['type' => 'asset'], $listing->getConditionVariables());
    }

    public function testEqualsFilterEscapes(): void
    {
        $filter = $this->createFilter();
        $listing = new NoteListing();

        $injectionKey = 'id` = 1) AND SLEEP(3)-- ';
        $params = new FilterParameter(columnFilters: [
            ['key' => $injectionKey, 'type' => 'equals', 'filterValue' => 'test'],
        ]);

        $filter->apply($params, $listing);

        $condition = $listing->getCondition();
        $this->assertStringContainsString('`id``', $condition);
        $this->assertStringNotContainsString('`id` =', $condition);
    }

    public function testEqualsFilterSkipsNonFilterParameter(): void
    {
        $filter = $this->createFilter();
        $listing = new NoteListing();

        $result = $filter->apply('not-a-filter-parameter', $listing);

        $this->assertSame($listing, $result);
        $this->assertEmpty($listing->getCondition());
    }

    private function createFilter(): EqualsFilter
    {
        $connection = $this->makeEmpty(Connection::class, [
            'quoteIdentifier' => function (string $identifier): string {
                return '`' . str_replace('`', '``', $identifier) . '`';
            },
        ]);
        $dbResolver = $this->makeEmpty(DbResolverInterface::class, [
            'get' => $connection,
        ]);

        return new EqualsFilter($dbResolver);
    }
}
