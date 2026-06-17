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

use Pimcore\Bundle\StudioBackendBundle\Entity\Search\SavedSearchConfiguration;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Search\Event\DetailedSavedSearchConfigurationEvent;
use Pimcore\Bundle\StudioBackendBundle\Search\Event\SavedSearchConfigurationEvent;
use Pimcore\Bundle\StudioBackendBundle\Search\Hydrator\ConfigurationHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\Search\Hydrator\DetailedConfigurationHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\Search\MappedParameter\SavedSearchParameter;
use Pimcore\Bundle\StudioBackendBundle\Search\Repository\SavedSearchConfigurationRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\Search\Schema\Configuration;
use Pimcore\Bundle\StudioBackendBundle\Search\Schema\DetailedConfiguration;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\UserPermissions;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @internal
 */
final readonly class SavedSearchConfigurationService implements SavedSearchConfigurationServiceInterface
{
    public function __construct(
        private ConfigurationHydratorInterface $configurationHydrator,
        private DetailedConfigurationHydratorInterface $detailedConfigurationHydrator,
        private EventDispatcherInterface $eventDispatcher,
        private SavedSearchConfigurationRepositoryInterface $repository,
        private SecurityServiceInterface $securityService,
        private ShareServiceInterface $shareService,
    ) {
    }

    /**
     * @throws NotFoundException
     * @throws ForbiddenException
     */
    public function getSavedSearchConfiguration(int $id): DetailedConfiguration
    {
        $configuration = $this->repository->getById($id);
        $currentUser = $this->securityService->getCurrentUser();

        if (!$this->shareService->isConfigurationAccessibleByUser($configuration, $currentUser)) {
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
     * @throws NotFoundException
     */
    public function saveConfiguration(SavedSearchParameter $parameter): Configuration
    {
        $configuration = new SavedSearchConfiguration();
        $configuration->setName($parameter->getName());
        $configuration->setDescription($parameter->getDescription());
        $configuration->setOwner($this->securityService->getCurrentUser()->getId());
        $configuration->setClassId($parameter->getClassId());
        $configuration->setColumns($parameter->getColumns());
        $configuration->setFilter($parameter->getFilters()?->toArray());
        $configuration->setCreateMenuShortcut($parameter->createMenuShortcut());

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
}
