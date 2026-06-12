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

/**
 * @internal
 */
final readonly class SavedSearchConfigurationRepository implements SavedSearchConfigurationRepositoryInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
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

    /**
     * @return SavedSearchConfiguration[]
     */
    public function getAll(): array
    {
        return $this->entityManager->getRepository(SavedSearchConfiguration::class)->findAll();
    }

    public function delete(SavedSearchConfiguration $configuration): void
    {
        $this->entityManager->remove($configuration);
        $this->entityManager->flush();
    }
}
