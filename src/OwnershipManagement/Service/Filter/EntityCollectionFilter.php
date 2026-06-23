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

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Pimcore\Bundle\StudioBackendBundle\OwnershipManagement\Service\SortOrderResolverInterface;
use function array_map;
use function is_numeric;
use function sprintf;

/**
 * @internal
 */
final readonly class EntityCollectionFilter implements EntityCollectionFilterInterface
{
    private const string ALIAS = 'c';

    private const array SORTABLE_FIELDS = ['id', 'name', 'owner', 'creationDate', 'modificationDate'];

    private const string DEFAULT_SORT_FIELD = 'modificationDate';

    public function __construct(
        private EntityManagerInterface $entityManager,
        private SortOrderResolverInterface $sortOrderResolver,
    ) {
    }

    public function findAllPaginated(
        string $entityClass,
        int $offset,
        int $limit,
        ?string $searchTerm = null,
        array $ownerIds = [],
        array $excludeOwnerIds = [],
        array $sortBy = [],
    ): array {
        $queryBuilder = $this->createFilteredQueryBuilder($entityClass, $searchTerm, $ownerIds, $excludeOwnerIds);
        $queryBuilder
            ->setFirstResult($offset)
            ->setMaxResults($limit);

        $this->applySorting($queryBuilder, $sortBy);

        return $queryBuilder->getQuery()->getResult();
    }

    public function countAll(
        string $entityClass,
        ?string $searchTerm = null,
        array $ownerIds = [],
        array $excludeOwnerIds = [],
    ): int {
        $queryBuilder = $this->createFilteredQueryBuilder($entityClass, $searchTerm, $ownerIds, $excludeOwnerIds)
            ->select(sprintf('COUNT(%s.id)', self::ALIAS));

        return (int) $queryBuilder->getQuery()->getSingleScalarResult();
    }

    public function getDistinctOwnerIds(string $entityClass): array
    {
        $owners = $this->entityManager->createQueryBuilder()
            ->select(sprintf('DISTINCT %s.owner', self::ALIAS))
            ->from($entityClass, self::ALIAS)
            ->getQuery()
            ->getSingleColumnResult();

        return array_map(static fn ($owner): int => (int) $owner, $owners);
    }

    /**
     * Matches the free-text search term against the configuration name, its id (when numeric)
     * and the owner (when the term resolved to one or more user ids), and optionally excludes
     * configurations owned by the given (e.g. deleted) users.
     *
     * @param class-string $entityClass
     * @param int[] $ownerIds
     * @param int[] $excludeOwnerIds
     */
    private function createFilteredQueryBuilder(
        string $entityClass,
        ?string $searchTerm,
        array $ownerIds,
        array $excludeOwnerIds,
    ): QueryBuilder {
        $alias = self::ALIAS;
        $queryBuilder = $this->entityManager->createQueryBuilder()
            ->select($alias)
            ->from($entityClass, $alias);

        if ($searchTerm !== null && $searchTerm !== '') {
            $searchExpression = $queryBuilder->expr()->orX(
                sprintf('%s.name LIKE :searchTerm', $alias)
            );
            $queryBuilder->setParameter('searchTerm', '%' . $searchTerm . '%');

            if (is_numeric($searchTerm)) {
                $searchExpression->add(sprintf('%s.id = :idSearch', $alias));
                $searchExpression->add(sprintf('%s.owner = :ownerIdSearch', $alias));
                $queryBuilder->setParameter('idSearch', (int) $searchTerm);
                $queryBuilder->setParameter('ownerIdSearch', (int) $searchTerm);
            }

            if ($ownerIds !== []) {
                $searchExpression->add(sprintf('%s.owner IN (:ownerIds)', $alias));
                $queryBuilder->setParameter('ownerIds', $ownerIds);
            }

            $queryBuilder->andWhere($searchExpression);
        }

        if ($excludeOwnerIds !== []) {
            $queryBuilder
                ->andWhere(sprintf('%s.owner NOT IN (:excludeOwnerIds)', $alias))
                ->setParameter('excludeOwnerIds', $excludeOwnerIds);
        }

        return $queryBuilder;
    }

    /**
     * @param array<array{field?: string, direction?: string}> $sortBy
     */
    private function applySorting(QueryBuilder $queryBuilder, array $sortBy): void
    {
        $applied = false;
        foreach ($this->sortOrderResolver->resolve(
            $sortBy,
            self::SORTABLE_FIELDS,
            self::DEFAULT_SORT_FIELD
        ) as $sort
        ) {
            $orderBy = sprintf('%s.%s', self::ALIAS, $sort['field']);

            if ($applied) {
                $queryBuilder->addOrderBy($orderBy, $sort['direction']);
            } else {
                $queryBuilder->orderBy($orderBy, $sort['direction']);
                $applied = true;
            }
        }
    }
}
