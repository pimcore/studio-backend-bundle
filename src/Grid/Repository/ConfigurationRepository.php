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

namespace Pimcore\Bundle\StudioBackendBundle\Grid\Repository;

use Doctrine\ORM\EntityManagerInterface;
use Pimcore\Bundle\StudioBackendBundle\Entity\Grid\GridConfiguration;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;

/**
 * @internal
 */
final readonly class ConfigurationRepository implements ConfigurationRepositoryInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function create(GridConfiguration $configuration): GridConfiguration
    {
        $configuration->setCreated();

        $this->entityManager->persist($configuration);
        $this->entityManager->flush();

        return $configuration;
    }

    public function update(GridConfiguration $configuration): GridConfiguration
    {
        $configuration->setModified();

        $this->entityManager->persist($configuration);
        $this->entityManager->flush();

        return $configuration;
    }

    public function clearShares(GridConfiguration $configuration): GridConfiguration
    {
        $configuration->clearShares();

        $this->entityManager->persist($configuration);
        $this->entityManager->flush();

        return $configuration;
    }

    /**
     * @throws NotFoundException
     */
    public function getById(int $id): GridConfiguration
    {
        $configuration = $this->entityManager->find(GridConfiguration::class, $id);
        if (!$configuration instanceof GridConfiguration) {
            throw new NotFoundException('Grid Configuration', $id);
        }

        return $configuration;
    }

    /**
     * @return GridConfiguration[]
     */
    public function getByAssetFolderId(int $folderId): array
    {
        return $this->entityManager->getRepository(GridConfiguration::class)->findBy(['assetFolderId' => $folderId]);
    }

    /**
     * @return GridConfiguration[]
     */
    public function getByClassId(string $classId): array
    {
        return $this->entityManager->getRepository(GridConfiguration::class)->findBy(['classId' => $classId]);
    }

    public function delete(GridConfiguration $configuration): void
    {
        $this->entityManager->remove($configuration);
        $this->entityManager->flush();
    }
}
