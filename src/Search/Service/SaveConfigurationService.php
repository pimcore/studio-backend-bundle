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

use Exception;
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\ClassDefinitionResolver;
use Pimcore\Bundle\StudioBackendBundle\Asset\Service\AssetServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\MappedParameter\ConfigurationParameter as DataObjectConfiguration;
use Pimcore\Bundle\StudioBackendBundle\Entity\Search\SavedSearchConfiguration;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Grid\Event\GridConfigurationEvent;
use Pimcore\Bundle\StudioBackendBundle\Grid\Hydrator\ConfigurationHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\Grid\Repository\ConfigurationRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\Grid\Service\UserRoleShareServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Search\MappedParameter\ConfigurationParameter;
use Pimcore\Bundle\StudioBackendBundle\Search\Schema\Configuration;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @internal
 */
final readonly class SaveConfigurationService implements SaveConfigurationServiceInterface
{
    public function __construct(
        private ConfigurationRepositoryInterface $gridConfigurationRepository,
        private UserRoleShareServiceInterface $userRoleShareService,
        private AssetServiceInterface $assetService,
        private SecurityServiceInterface $securityService,
        private ConfigurationHydratorInterface $configurationHydrator,
        private EventDispatcherInterface $eventDispatcher,
        private ClassDefinitionResolver $classDefinitionResolver
    ) {
    }

    /**
     * @throws NotFoundException
     */
    public function saveConfiguration(ConfigurationParameter $configuration, ?string $classId = null): Configuration
    {
        if (!empty($classId) && !$this->classDefinitionResolver->getById($classId)) {
            throw new NotFoundException('ClassID', $classId);
        }

        $gridConfiguration = new SavedSearchConfiguration();
        $gridConfiguration = $this->setDefaultGridConfigurationData($gridConfiguration, $configuration);

        $gridConfiguration = $this->gridConfigurationRepository->create($gridConfiguration);

        $hydratedConfiguration = $this->configurationHydrator->hydrate($gridConfiguration);

        $this->dispatchEvent($hydratedConfiguration);

        return $hydratedConfiguration;
    }

    private function setDefaultGridConfigurationData(
        SavedSearchConfiguration $gridConfiguration,
        ConfigurationParameter $configuration
    ): SavedSearchConfiguration {
        $gridConfiguration->setName($configuration->getName());
        $gridConfiguration->setDescription($configuration->getDescription());
        $gridConfiguration->setOwner($this->securityService->getCurrentUser()->getId());
        $gridConfiguration->setColumns($configuration->getColumnsAsArray());

        if ($configuration->saveFilter()) {
            $gridConfiguration->setFilter($configuration->getFilter()->toArray());
        }

        if ($this->securityService->getCurrentUser()->isAllowed('share_configurations')) {
            $gridConfiguration = $this->userRoleShareService->setShareOptions($gridConfiguration, $configuration);
        }

        return $gridConfiguration;
    }

    private function dispatchEvent(Configuration $configuration): void
    {
        $this->eventDispatcher->dispatch(
            new GridConfigurationEvent($configuration),
            GridConfigurationEvent::EVENT_NAME
        );
    }
}
