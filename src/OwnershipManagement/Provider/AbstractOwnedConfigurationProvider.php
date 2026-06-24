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

namespace Pimcore\Bundle\StudioBackendBundle\OwnershipManagement\Provider;

use Pimcore\Bundle\StudioBackendBundle\OwnershipManagement\Hydrator\OwnershipConfigurationHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\OwnershipManagement\Query\OwnershipListQuery;
use Pimcore\Bundle\StudioBackendBundle\OwnershipManagement\Query\OwnershipSort;
use Pimcore\Bundle\StudioBackendBundle\OwnershipManagement\Schema\OwnershipConfiguration;
use Pimcore\Bundle\StudioBackendBundle\Response\Collection;
use Pimcore\Bundle\StudioBackendBundle\User\Schema\SimpleUser;
use Pimcore\Bundle\StudioBackendBundle\User\Service\UserServiceInterface;
use function array_filter;
use function array_map;
use function array_values;

/**
 * Shared listing flow for entity configuration providers.
 * Concrete providers only wire their repository and entity hydration.
 *
 * @internal
 */
abstract readonly class AbstractOwnedConfigurationProvider implements OwnershipProviderInterface
{
    public function __construct(
        protected OwnershipConfigurationHydratorInterface $ownershipConfigurationHydrator,
        private UserServiceInterface $userService,
    ) {
    }

    public function listConfigurations(OwnershipListQuery $query): Collection
    {
        $searchTerm = $query->getSearchTerm();
        $ownerIds = $searchTerm === null ? [] : $this->resolveMatchingOwnerIds($searchTerm);
        $excludeOwnerIds = $query->includeDeletedOwners() ? [] : $this->resolveDeletedOwnerIds();
        $sortBy = array_map(
            static fn (OwnershipSort $sort): array => [
                'field' => $sort->getField(),
                'direction' => $sort->getDirection(),
            ],
            $query->getSortBy()
        );

        $configurations = $this->findAllPaginated(
            $query->getOffset(),
            $query->getLimit(),
            $searchTerm,
            $ownerIds,
            $excludeOwnerIds,
            $sortBy,
        );

        $ownerNames = $this->userService->getUserNamesByIds(
            array_map(fn (object $configuration): int => $this->extractOwnerId($configuration), $configurations)
        );

        $items = array_map(
            fn (object $configuration): OwnershipConfiguration =>
            $this->hydrateConfiguration($configuration, $ownerNames),
            $configurations
        );

        $totalItems = $this->countAll($searchTerm, $ownerIds, $excludeOwnerIds);

        return new Collection($totalItems, $items);
    }

    /**
     * @param int[] $ownerIds
     * @param int[] $excludeOwnerIds
     * @param array<array{field?: string, direction?: string}> $sortBy
     *
     * @return object[]
     */
    abstract protected function findAllPaginated(
        int $offset,
        int $limit,
        ?string $searchTerm,
        array $ownerIds,
        array $excludeOwnerIds,
        array $sortBy,
    ): array;

    /**
     * @param int[] $ownerIds
     * @param int[] $excludeOwnerIds
     */
    abstract protected function countAll(?string $searchTerm, array $ownerIds, array $excludeOwnerIds): int;

    /**
     * @return int[]
     */
    abstract protected function getDistinctOwnerIds(): array;

    /**
     * Returns the owner id of the configuration so owner names can be resolved in bulk.
     */
    abstract protected function extractOwnerId(object $configuration): int;

    /**
     * @param array<int, string> $ownerNames Resolved usernames keyed by owner id (missing id = deleted owner).
     */
    abstract protected function hydrateConfiguration(object $configuration, array $ownerNames): OwnershipConfiguration;

    /**
     * Resolves the user ids whose name/email matches the search term, so the listing can
     * also surface configurations by their owner without joining the user table.
     *
     * @return int[]
     */
    private function resolveMatchingOwnerIds(string $searchTerm): array
    {
        $ownerIds = [];
        foreach ($this->userService->userSearch($searchTerm)->getItems() as $user) {
            /** @var SimpleUser $user */
            $ownerIds[] = $user->getId();
        }

        return $ownerIds;
    }

    /**
     * Resolves the owner ids referenced by configurations whose user no longer exists, so the
     * listing can exclude them at query level when deleted owners should be hidden.
     *
     * @return int[]
     */
    private function resolveDeletedOwnerIds(): array
    {
        $ownerIds = $this->getDistinctOwnerIds();
        $existingOwnerNames = $this->userService->getUserNamesByIds($ownerIds);

        return array_values(
            array_filter($ownerIds, static fn (int $ownerId): bool => !isset($existingOwnerNames[$ownerId]))
        );
    }
}
