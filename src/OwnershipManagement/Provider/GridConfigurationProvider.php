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

use Pimcore\Bundle\StudioBackendBundle\Entity\Grid\GridConfiguration;
use Pimcore\Bundle\StudioBackendBundle\Grid\Repository\ConfigurationRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\OwnershipManagement\Hydrator\OwnershipConfigurationHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\OwnershipManagement\Schema\OwnershipConfiguration;
use Pimcore\Bundle\StudioBackendBundle\User\Service\UserServiceInterface;

/**
 * @internal
 */
final readonly class GridConfigurationProvider extends AbstractOwnedConfigurationProvider
{
    private const string TYPE = 'grid_configuration';

    public function __construct(
        private ConfigurationRepositoryInterface $configurationRepository,
        OwnershipConfigurationHydratorInterface $ownershipConfigurationHydrator,
        UserServiceInterface $userService,
    ) {
        parent::__construct($ownershipConfigurationHydrator, $userService);
    }

    public function getType(): string
    {
        return self::TYPE;
    }

    public function getLabel(): string
    {
        return 'ownership_management_type_grid_configuration';
    }

    public function getIcon(): string
    {
        return 'settings';
    }

    public function getSortPriority(): int
    {
        return 30;
    }

    public function reassignOwner(array $ids, int $newOwnerId): void
    {
        foreach ($ids as $id) {
            $configuration = $this->configurationRepository->getById((int) $id);
            $configuration->setOwner($newOwnerId);
            $this->configurationRepository->update($configuration);
        }
    }

    public function delete(array $ids): void
    {
        foreach ($ids as $id) {
            $this->configurationRepository->delete(
                $this->configurationRepository->getById((int) $id)
            );
        }
    }

    protected function findAllPaginated(
        int $offset,
        int $limit,
        ?string $searchTerm,
        array $ownerIds,
        array $excludeOwnerIds,
        array $sortBy,
    ): array {
        return $this->configurationRepository->findAllPaginated(
            $offset,
            $limit,
            $searchTerm,
            $ownerIds,
            $excludeOwnerIds,
            $sortBy,
        );
    }

    protected function countAll(?string $searchTerm, array $ownerIds, array $excludeOwnerIds): int
    {
        return $this->configurationRepository->countAll($searchTerm, $ownerIds, $excludeOwnerIds);
    }

    protected function getDistinctOwnerIds(): array
    {
        return $this->configurationRepository->getDistinctOwnerIds();
    }

    protected function extractOwnerId(object $configuration): int
    {
        /** @var GridConfiguration $configuration */
        return $configuration->getOwner() ?? 0;
    }

    protected function hydrateConfiguration(object $configuration, array $ownerNames): OwnershipConfiguration
    {
        /** @var GridConfiguration $configuration */
        $ownerId = $configuration->getOwner();

        return $this->ownershipConfigurationHydrator->hydrate(
            (string) $configuration->getId(),
            self::TYPE,
            $configuration->getName(),
            $ownerId ?? 0,
            $ownerId === null ? null : ($ownerNames[$ownerId] ?? null),
            $configuration->getCreationDate()->getTimestamp(),
            $configuration->getModificationDate()->getTimestamp(),
        );
    }
}
