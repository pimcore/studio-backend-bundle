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

namespace Pimcore\Bundle\StudioBackendBundle\Search\Service;

use Pimcore\Bundle\StudioBackendBundle\Configuration\Share\Service\ConfigurationShareServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Entity\Search\SavedSearchConfiguration;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\CollectionParameters;
use Pimcore\Bundle\StudioBackendBundle\Response\Collection;
use Pimcore\Bundle\StudioBackendBundle\Search\Event\PreResponse\DetailedSavedSearchConfigurationEvent;
use Pimcore\Bundle\StudioBackendBundle\Search\Event\PreResponse\SavedSearchConfigurationEvent;
use Pimcore\Bundle\StudioBackendBundle\Search\Event\PreResponse\SavedSearchConfigurationListItemEvent;
use Pimcore\Bundle\StudioBackendBundle\Search\Hydrator\ConfigurationHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\Search\Hydrator\DetailedConfigurationHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\Search\Hydrator\ListItemHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\Search\MappedParameter\SavedSearchParameter;
use Pimcore\Bundle\StudioBackendBundle\Search\MappedParameter\UpdateMenuShortcutParameter;
use Pimcore\Bundle\StudioBackendBundle\Search\Repository\SavedSearchConfigurationRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\Search\Schema\Configuration;
use Pimcore\Bundle\StudioBackendBundle\Search\Schema\DetailedConfiguration;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\UserPermissions;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use function array_filter;
use function array_slice;
use function array_values;
use function count;

/**
 * @internal
 */
final readonly class SavedSearchConfigurationService implements SavedSearchConfigurationServiceInterface
{
    public function __construct(
        private ConfigurationHydratorInterface $configurationHydrator,
        private DetailedConfigurationHydratorInterface $detailedConfigurationHydrator,
        private ListItemHydratorInterface $listItemHydrator,
        private EventDispatcherInterface $eventDispatcher,
        private SavedSearchConfigurationRepositoryInterface $repository,
        private SecurityServiceInterface $securityService,
        private ConfigurationShareServiceInterface $shareService,
    ) {
    }

    public function listConfigurations(CollectionParameters $parameters, ?string $searchTerm): Collection
    {
        $currentUser = $this->securityService->getCurrentUser();
        $userId = $currentUser->getId();

        $accessibleConfigurations = array_values(
            array_filter(
                $this->repository->getList($searchTerm),
                fn (SavedSearchConfiguration $configuration): bool => $this->shareService
                    ->isConfigurationSharedWithUser($configuration, $currentUser)
            )
        );

        $items = [];
        $pagedConfigurations = array_slice(
            $accessibleConfigurations,
            $parameters->getOffset(),
            $parameters->getPageSize()
        );
        foreach ($pagedConfigurations as $configuration) {
            $schema = $this->listItemHydrator->hydrate(
                $configuration,
                $configuration->getOwner() === $userId
            );

            $this->eventDispatcher->dispatch(
                new SavedSearchConfigurationListItemEvent($schema),
                SavedSearchConfigurationListItemEvent::EVENT_NAME
            );

            $items[] = $schema;
        }

        return new Collection(count($accessibleConfigurations), $items);
    }

    public function listMenuShortcutConfigurations(): Collection
    {
        $currentUser = $this->securityService->getCurrentUser();
        $userId = $currentUser->getId();

        $accessibleConfigurations = array_values(
            array_filter(
                $this->repository->getMenuShortcutList(),
                fn (SavedSearchConfiguration $configuration): bool => $this->shareService
                    ->isConfigurationSharedWithUser($configuration, $currentUser)
            )
        );

        $items = [];
        foreach ($accessibleConfigurations as $configuration) {
            $schema = $this->listItemHydrator->hydrate(
                $configuration,
                $configuration->getOwner() === $userId
            );

            $this->eventDispatcher->dispatch(
                new SavedSearchConfigurationListItemEvent($schema),
                SavedSearchConfigurationListItemEvent::EVENT_NAME
            );

            $items[] = $schema;
        }

        return new Collection(count($items), $items);
    }

    /**
     * {@inheritdoc}
     */
    public function getSavedSearchConfiguration(int $id): DetailedConfiguration
    {
        $configuration = $this->repository->getById($id);
        $currentUser = $this->securityService->getCurrentUser();

        if (!$this->shareService->isConfigurationSharedWithUser($configuration, $currentUser)) {
            throw new ForbiddenException(
                'You are not allowed to access this saved search configuration.'
            );
        }

        $schema = $this->detailedConfigurationHydrator->hydrate(
            $configuration,
            $this->shareService->getUserShares($configuration),
            $this->shareService->getRoleShares($configuration),
        );

        $this->eventDispatcher->dispatch(
            new DetailedSavedSearchConfigurationEvent($schema),
            DetailedSavedSearchConfigurationEvent::EVENT_NAME
        );

        return $schema;
    }

    /**
     * {@inheritdoc}
     */
    public function saveConfiguration(SavedSearchParameter $parameter): Configuration
    {
        $configuration = new SavedSearchConfiguration();
        $configuration->setName($parameter->getName());
        $configuration->setDescription($parameter->getDescription());
        $configuration->setOwner($this->securityService->getCurrentUser()->getId());
        $configuration->setClassId($parameter->getClassId());
        $configuration->setColumns($parameter->getColumnsAsArray());
        $configuration->setFilter($parameter->getFilter()?->toArray());
        $configuration->setCreateMenuShortcut($parameter->createMenuShortcut());
        $configuration->setMenuShortcutGroup($parameter->getMenuShortcutGroup());

        if ($this->securityService->getCurrentUser()->isAllowed(UserPermissions::SHARE_CONFIGURATIONS->value)) {
            $configuration = $this->shareService->setShareOptions($configuration, $parameter);
        }

        $configuration = $this->repository->create($configuration);

        $schema = $this->configurationHydrator->hydrate($configuration);

        $this->eventDispatcher->dispatch(
            new SavedSearchConfigurationEvent($schema),
            SavedSearchConfigurationEvent::EVENT_NAME
        );

        return $schema;
    }

    /**
     * {@inheritdoc}
     */
    public function updateConfiguration(SavedSearchParameter $parameter, int $id): void
    {
        $configuration = $this->repository->getById($id);

        if ($configuration->getOwner() !== $this->securityService->getCurrentUser()->getId()) {
            throw new ForbiddenException('You are not allowed to update this configuration.');
        }

        $configuration = $this->repository->clearShares($configuration);

        $configuration->setName($parameter->getName());
        $configuration->setDescription($parameter->getDescription());
        $configuration->setClassId($parameter->getClassId());
        $configuration->setColumns($parameter->getColumnsAsArray());
        $configuration->setFilter($parameter->getFilter()?->toArray());
        $configuration->setCreateMenuShortcut($parameter->createMenuShortcut());
        $configuration->setMenuShortcutGroup($parameter->getMenuShortcutGroup());

        if ($this->securityService->getCurrentUser()->isAllowed(UserPermissions::SHARE_CONFIGURATIONS->value)) {
            $configuration = $this->shareService->setShareOptions($configuration, $parameter);
        }

        $this->repository->update($configuration);
    }

    /**
     * {@inheritdoc}
     */
    public function updateMenuShortcut(UpdateMenuShortcutParameter $parameter, int $id): void
    {
        $configuration = $this->repository->getById($id);

        if ($configuration->getOwner() !== $this->securityService->getCurrentUser()->getId()) {
            throw new ForbiddenException('You are not allowed to update this configuration.');
        }

        $configuration->setCreateMenuShortcut($parameter->createMenuShortcut());
        $configuration->setMenuShortcutGroup($parameter->getMenuShortcutGroup());

        $this->repository->update($configuration);
    }

    /**
     * {@inheritdoc}
     */
    public function deleteConfiguration(int $id): void
    {
        $configuration = $this->repository->getById($id);

        if ($this->securityService->getCurrentUser()->getId() !== $configuration->getOwner()) {
            throw new ForbiddenException(
                'You are not allowed to delete this configuration. Only the owner can delete it.'
            );
        }

        $this->repository->delete($configuration);
    }
}
