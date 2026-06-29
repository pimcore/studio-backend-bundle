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

namespace Pimcore\Bundle\StudioBackendBundle\Search\Repository;

use Doctrine\ORM\EntityManagerInterface;
use Pimcore\Bundle\StudioBackendBundle\Entity\Search\SavedSearchConfiguration;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\OwnershipManagement\Service\Filter\EntityCollectionFilterInterface;

/**
 * @internal
 */
final readonly class SavedSearchConfigurationRepository implements SavedSearchConfigurationRepositoryInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private EntityCollectionFilterInterface $entityCollectionFilter,
    ) {
    }

    /**
     * @throws NotFoundException
     */
    public function getById(int $id): SavedSearchConfiguration
    {
        $configuration = $this->entityManager->find(SavedSearchConfiguration::class, $id);
        if (!$configuration instanceof SavedSearchConfiguration) {
            throw new NotFoundException('Saved Search Configuration', $id);
        }

        return $configuration;
    }

    public function getList(?string $searchTerm, ?string $sortBy = null, ?string $sortOrder = null): array
    {
        // Whitelist the sortable fields to keep the ORDER BY safe; default to newest first.
        $sortField = match ($sortBy) {
            'name' => 'c.name',
            'modificationDate' => 'c.modificationDate',
            default => 'c.modificationDate',
        };
        $direction = strtoupper((string) $sortOrder) === 'ASC' ? 'ASC' : 'DESC';

        $queryBuilder = $this->entityManager->createQueryBuilder()
            ->select('c')
            ->from(SavedSearchConfiguration::class, 'c')
            ->orderBy($sortField, $direction);

        $searchTerm = $searchTerm !== null ? trim($searchTerm) : null;
        if ($searchTerm !== null && $searchTerm !== '') {
            $queryBuilder
                ->where('LOWER(c.name) LIKE LOWER(:searchTerm)')
                ->setParameter('searchTerm', '%' . $searchTerm . '%');
        }

        return $queryBuilder->getQuery()->getResult();
    }

    public function getMenuShortcutList(): array
    {
        return $this->entityManager->createQueryBuilder()
            ->select('c')
            ->from(SavedSearchConfiguration::class, 'c')
            ->where('c.createMenuShortcut = :createMenuShortcut')
            ->setParameter('createMenuShortcut', true)
            ->orderBy('c.modificationDate', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function create(SavedSearchConfiguration $configuration): SavedSearchConfiguration
    {
        $configuration->setCreated();

        $this->entityManager->persist($configuration);
        $this->entityManager->flush();

        return $configuration;
    }

    public function update(SavedSearchConfiguration $configuration): SavedSearchConfiguration
    {
        $configuration->setModified();

        $this->entityManager->persist($configuration);
        $this->entityManager->flush();

        return $configuration;
    }

    public function clearShares(SavedSearchConfiguration $configuration): SavedSearchConfiguration
    {
        $configuration->clearShares();

        $this->entityManager->persist($configuration);
        $this->entityManager->flush();

        return $configuration;
    }

    public function findAllPaginated(
        int $offset,
        int $limit,
        ?string $searchTerm = null,
        array $ownerIds = [],
        array $excludeOwnerIds = [],
        array $sortBy = [],
    ): array {
        return $this->entityCollectionFilter->findAllPaginated(
            SavedSearchConfiguration::class,
            $offset,
            $limit,
            $searchTerm,
            $ownerIds,
            $excludeOwnerIds,
            $sortBy,
        );
    }

    public function countAll(?string $searchTerm = null, array $ownerIds = [], array $excludeOwnerIds = []): int
    {
        return $this->entityCollectionFilter->countAll(
            SavedSearchConfiguration::class,
            $searchTerm,
            $ownerIds,
            $excludeOwnerIds,
        );
    }

    public function getDistinctOwnerIds(): array
    {
        return $this->entityCollectionFilter->getDistinctOwnerIds(SavedSearchConfiguration::class);
    }

    public function delete(SavedSearchConfiguration $configuration): void
    {
        $this->entityManager->remove($configuration);
        $this->entityManager->flush();
    }
}
