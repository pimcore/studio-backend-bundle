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

namespace Pimcore\Bundle\StudioBackendBundle\OwnershipManagement\Service\Filter;

use Pimcore\Bundle\StudioBackendBundle\OwnershipManagement\Query\OwnershipListQuery;
use Pimcore\Bundle\StudioBackendBundle\OwnershipManagement\Query\OwnershipSort;
use Pimcore\Bundle\StudioBackendBundle\OwnershipManagement\Schema\OwnershipConfiguration;
use Pimcore\Bundle\StudioBackendBundle\Response\Collection;
use Pimcore\Bundle\StudioBackendBundle\User\Schema\SimpleUser;
use Pimcore\Bundle\StudioBackendBundle\User\Service\UserServiceInterface;
use function array_filter;
use function array_map;
use function array_slice;
use function array_values;
use function count;
use function in_array;
use function is_numeric;
use function stripos;
use function strtoupper;
use function usort;

/**
 * @internal
 */
final readonly class InMemoryCollectionFilter implements InMemoryCollectionFilterInterface
{
    private const string SORT_BY_OWNER = 'owner';

    private const string SORT_BY_ID = 'id';

    private const string SORT_BY_CREATION_DATE = 'creationDate';

    private const string SORT_BY_MODIFICATION_DATE = 'modificationDate';

    public function __construct(
        private UserServiceInterface $userService,
    ) {
    }

    public function apply(array $items, OwnershipListQuery $query): Collection
    {
        $items = $this->filterByOwnerState($items, $query->includeDeletedOwners());
        $items = $this->filterBySearchTerm($items, $query->getSearchTerm());

        $this->sortItems($items, $query->getSortBy());

        $totalItems = count($items);
        $pageItems = array_slice($items, $query->getOffset(), $query->getLimit());

        return new Collection($totalItems, $pageItems);
    }

    /**
     * @param OwnershipConfiguration[] $items
     *
     * @return list<OwnershipConfiguration>
     */
    private function filterByOwnerState(array $items, bool $includeDeletedOwners): array
    {
        if ($includeDeletedOwners) {
            return array_values($items);
        }

        return array_values(
            array_filter($items, static fn (OwnershipConfiguration $item): bool => !$item->isOwnerDeleted())
        );
    }

    /**
     * @param OwnershipConfiguration[] $items
     *
     * @return list<OwnershipConfiguration>
     */
    private function filterBySearchTerm(array $items, ?string $searchTerm): array
    {
        if ($searchTerm === null || $searchTerm === '') {
            return array_values($items);
        }

        $ownerIds = $this->resolveMatchingOwnerIds($searchTerm);

        return array_values(
            array_filter(
                $items,
                static fn (OwnershipConfiguration $item): bool =>
                    stripos($item->getName(), $searchTerm) !== false
                    || (is_numeric($searchTerm) && $item->getId() === $searchTerm)
                    || (is_numeric($searchTerm) && $item->getOwnerId() === (int) $searchTerm)
                    || in_array($item->getOwnerId(), $ownerIds, true)
            )
        );
    }

    /**
     * Resolves the user ids whose name/email/id matches the search term, mirroring the
     * database-backed filter so configurations can also be found by their owner (e.g. by user id).
     *
     * @return int[]
     */
    private function resolveMatchingOwnerIds(string $searchTerm): array
    {
        return array_map(
            static fn (SimpleUser $user): int => $user->getId(),
            $this->userService->userSearch($searchTerm)->getItems()
        );
    }

    /**
     * @param OwnershipConfiguration[] $items
     * @param OwnershipSort[] $sortBy
     */
    private function sortItems(array &$items, array $sortBy): void
    {
        if ($sortBy === []) {
            return;
        }

        usort(
            $items,
            function (OwnershipConfiguration $a, OwnershipConfiguration $b) use ($sortBy): int {
                foreach ($sortBy as $sort) {
                    $factor = strtoupper($sort->getDirection()) === 'DESC' ? -1 : 1;
                    $comparison = $factor * $this->compare($a, $b, $sort->getField());

                    if ($comparison !== 0) {
                        return $comparison;
                    }
                }

                return 0;
            }
        );
    }

    private function compare(OwnershipConfiguration $a, OwnershipConfiguration $b, string $field): int
    {
        return match ($field) {
            self::SORT_BY_OWNER => $a->getOwnerId() <=> $b->getOwnerId(),
            self::SORT_BY_ID => $a->getId() <=> $b->getId(),
            self::SORT_BY_CREATION_DATE => ($a->getCreationDate() ?? 0) <=> ($b->getCreationDate() ?? 0),
            self::SORT_BY_MODIFICATION_DATE => ($a->getModificationDate() ?? 0) <=> ($b->getModificationDate() ?? 0),
            default => $a->getName() <=> $b->getName(),
        };
    }
}
