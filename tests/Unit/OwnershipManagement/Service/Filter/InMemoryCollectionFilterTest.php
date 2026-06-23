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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\OwnershipManagement\Service\Filter;

use Codeception\Test\Unit;
use Pimcore\Bundle\StudioBackendBundle\OwnershipManagement\Query\OwnershipListQuery;
use Pimcore\Bundle\StudioBackendBundle\OwnershipManagement\Query\OwnershipSort;
use Pimcore\Bundle\StudioBackendBundle\OwnershipManagement\Schema\OwnershipConfiguration;
use Pimcore\Bundle\StudioBackendBundle\OwnershipManagement\Service\Filter\InMemoryCollectionFilter;
use Pimcore\Bundle\StudioBackendBundle\Response\Collection;
use Pimcore\Bundle\StudioBackendBundle\User\Schema\SimpleUser;
use Pimcore\Bundle\StudioBackendBundle\User\Service\UserServiceInterface;

/**
 * @internal
 */
final class InMemoryCollectionFilterTest extends Unit
{
    public function testFiltersBySearchTermAcrossNameIdAndOwner(): void
    {
        $filter = $this->createFilter(['alice' => new Collection(1, [new SimpleUser(10, 'alice')])]);

        $byOwner = $filter->apply($this->items(), $this->query(searchTerm: 'alice'));
        $this->assertSame(['1'], $this->ids($byOwner->getItems()));

        $byName = $filter->apply($this->items(), $this->query(searchTerm: 'Sales'));
        $this->assertSame(['2'], $this->ids($byName->getItems()));

        $byId = $filter->apply($this->items(), $this->query(searchTerm: '99'));
        $this->assertSame(['99'], $this->ids($byId->getItems()));
    }

    public function testSearchByOwnerIdMatchesViaUserSearch(): void
    {
        $filter = $this->createFilter(['14' => new Collection(1, [new SimpleUser(14, 'superuser')])]);

        $items = [
            new OwnershipConfiguration('5', 'dashboard', 'My Global Share', 14, 'superuser'),
            new OwnershipConfiguration('6', 'dashboard', 'Other', 99, 'someone'),
        ];

        $result = $filter->apply($items, $this->query(searchTerm: '14'));

        $this->assertSame(['5'], $this->ids($result->getItems()));
    }

    public function testSearchByDeletedOwnerIdMatchesStoredOwnerId(): void
    {
        // The owner (user 14) was deleted, so userSearch returns nothing for "14",
        // but the configuration still stores ownerId 14 and must be findable by it.
        $filter = $this->createFilter();

        $items = [
            new OwnershipConfiguration('5', 'dashboard', 'Orphaned', 14, null, true),
            new OwnershipConfiguration('6', 'dashboard', 'Other', 99, 'someone'),
        ];

        $result = $filter->apply($items, $this->query(searchTerm: '14'));

        $this->assertSame(['5'], $this->ids($result->getItems()));
    }

    public function testSearchByNumericTermMatchesIdExactlyNotAsSubstring(): void
    {
        $filter = $this->createFilter();

        $items = [
            new OwnershipConfiguration('20', 'dashboard', 'Alpha', 5, 'bob'),
            new OwnershipConfiguration('201', 'dashboard', 'Beta', 6, 'carol'),
            new OwnershipConfiguration('120', 'dashboard', 'Gamma', 7, 'dave'),
        ];

        $result = $filter->apply($items, $this->query(searchTerm: '20'));

        $this->assertSame(['20'], $this->ids($result->getItems()));
    }

    public function testExcludesDeletedOwnersWhenRequested(): void
    {
        $filter = $this->createFilter();

        $result = $filter->apply($this->items(), $this->query(includeDeletedOwners: false));

        $this->assertSame(2, $result->getTotalItems());
        $this->assertSame(['1', '2'], $this->ids($result->getItems()));
    }

    public function testIncludesDeletedOwnersByDefault(): void
    {
        $filter = $this->createFilter();

        $result = $filter->apply($this->items(), $this->query());

        $this->assertSame(3, $result->getTotalItems());
    }

    public function testPaginatesWhileKeepingTotalCount(): void
    {
        $filter = $this->createFilter();

        $result = $filter->apply($this->items(), $this->query(limit: 2));

        $this->assertSame(3, $result->getTotalItems());
        $this->assertCount(2, $result->getItems());
    }

    /**
     * @param array<string, Collection> $usersByTerm
     */
    private function createFilter(array $usersByTerm = []): InMemoryCollectionFilter
    {
        $userService = $this->makeEmpty(UserServiceInterface::class, [
            'userSearch' => function (string $searchQuery) use ($usersByTerm): Collection {
                return $usersByTerm[$searchQuery] ?? new Collection(0, []);
            },
        ]);

        return new InMemoryCollectionFilter($userService);
    }

    /**
     * @return OwnershipConfiguration[]
     */
    private function items(): array
    {
        return [
            new OwnershipConfiguration('1', 'dashboard', 'Quarterly report', 10, 'alice'),
            new OwnershipConfiguration('2', 'dashboard', 'Sales', 11, 'bob'),
            new OwnershipConfiguration('99', 'dashboard', 'Other', 12, null, true),
        ];
    }

    private function query(
        int $offset = 0,
        int $limit = 50,
        ?string $searchTerm = null,
        bool $includeDeletedOwners = true,
    ): OwnershipListQuery {
        return new OwnershipListQuery(
            $offset,
            $limit,
            $searchTerm,
            $includeDeletedOwners,
            [new OwnershipSort('name', 'ASC')],
        );
    }

    /**
     * @param OwnershipConfiguration[] $items
     *
     * @return string[]
     */
    private function ids(array $items): array
    {
        return array_map(static fn (OwnershipConfiguration $item): string => $item->getId(), $items);
    }
}
