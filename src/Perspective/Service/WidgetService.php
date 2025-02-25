<?php
declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Bundle\StudioBackendBundle\Perspective\Service;

use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ElementSavingFailedException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotWriteableException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ValidationFailedException;
use Pimcore\Bundle\StudioBackendBundle\Exception\MustImplementInterfaceException;
use Pimcore\Bundle\StudioBackendBundle\Perspective\Event\WidgetConfigEvent;
use Pimcore\Bundle\StudioBackendBundle\Perspective\Event\WidgetTypeEvent;
use Pimcore\Bundle\StudioBackendBundle\Perspective\Hydrator\WidgetConfigHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\Perspective\Hydrator\WidgetConfigListHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\Perspective\Hydrator\WidgetTypeHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\Perspective\MappedParameter\WidgetDataParameter;
use Pimcore\Bundle\StudioBackendBundle\Perspective\Repository\WidgetConfigRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\Perspective\Schema\WidgetConfig;
use Pimcore\Bundle\StudioBackendBundle\Perspective\Schema\WidgetType;
use Pimcore\Bundle\StudioBackendBundle\Perspective\Service\Loader\Widget\ConfigHydratorLoaderInterface;
use Pimcore\Bundle\StudioBackendBundle\Perspective\Service\Loader\Widget\ConfigRepositoryLoaderInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\ValidateConfigurationTrait;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Uid\Factory\UuidFactory;
use function sprintf;

/**
 * @internal
 */
final readonly class WidgetService implements WidgetServiceInterface
{
    use ValidateConfigurationTrait;

    public function __construct(
        private ConfigHydratorLoaderInterface $configHydratorLoader,
        private ConfigRepositoryLoaderInterface $configRepositoryLoader,
        private EventDispatcherInterface $eventDispatcher,
        private UuidFactory $uuidFactory,
        private WidgetTypeHydratorInterface $widgetTypeHydrator,
        private WidgetConfigListHydratorInterface $configListHydrator,
        private WidgetValidationServiceInterface $widgetValidationService,
        private array $widgetTypes
    ) {
    }

    /**
     * @return WidgetType[]
     */
    public function listWidgetTypes(): array
    {
        $widgetTypes = [];
        foreach ($this->widgetTypes as $type) {
            $hydratedType = $this->widgetTypeHydrator->hydrate($type);
            $this->eventDispatcher->dispatch(new WidgetTypeEvent($hydratedType), WidgetTypeEvent::EVENT_NAME);
            $widgetTypes[] = $hydratedType;
        }

        return $widgetTypes;
    }

    /**
     * @throws ElementSavingFailedException|InvalidArgumentException|NotFoundException
     */
    public function addWidgetConfig(string $widgetType, WidgetDataParameter $widgetData): string
    {
        $this->widgetValidationService->validateWidgetType($widgetType);
        $configData = $widgetData->getData();

        $configData['name'] = $this->getValidConfigName($configData);
        $configData['id'] = $this->getValidConfigId($this->uuidFactory);

        return $this->loadRepositoryByType($widgetType)->createConfiguration($configData);
    }

    /**
     * @throws ElementSavingFailedException|InvalidArgumentException|NotFoundException|ValidationFailedException
     */
    public function updateWidgetConfig(string $widgetType, string $widgetId, WidgetDataParameter $widgetData): void
    {
        $this->widgetValidationService->validateWidgetType($widgetType);
        $repository = $this->loadRepositoryByType($widgetType);
        $repository->getConfiguration($widgetId);

        $configData = $widgetData->getData();
        $configData['name'] = $this->getValidConfigName($configData);
        $configData['id'] = $widgetId;
        $repository->updateConfiguration($configData);
    }

    /**
     * @throws ForbiddenException|InvalidArgumentException|NotFoundException|NotWriteableException
     */
    public function getWidgetConfigData(string $widgetType, string $widgetId): WidgetConfig
    {
        $this->widgetValidationService->validateWidgetType($widgetType);
        $data = $this->loadRepositoryByType($widgetType)->getConfiguration($widgetId);
        $hydrated = $this->loadHydratorByType($widgetType)->hydrate($data);
        $this->dispatchConfigEvent($hydrated);

        return $hydrated;
    }

    /**
     * @throws InvalidArgumentException|NotFoundException
     *
     * @return WidgetConfig[]
     */
    public function listWidgetConfigurations(): array
    {
        $hydrated = [];
        foreach ($this->loadRepositories() as $repository) {
            $widgetType = $repository->getSupportedWidgetType();
            $this->widgetValidationService->validateWidgetType($widgetType);
            foreach ($repository->listConfigurations() as $configData) {
                $hydrated[] = $this->processRepositoryConfiguration($configData, $widgetType);
            }
        }

        return $hydrated;
    }

    /**
     * @throws InvalidArgumentException|NotFoundException|NotWriteableException
     */
    public function deleteWidgetConfig(string $widgetType, string $widgetId): void
    {
        $this->widgetValidationService->validateWidgetType($widgetType);
        $this->loadRepositoryByType($widgetType)->deleteConfiguration($widgetId);
    }

    /**
     * @throws InvalidArgumentException
     */
    public function loadHydratorByType(string $widgetType): WidgetConfigHydratorInterface
    {
        try {
            return $this->configHydratorLoader->loadHydrator($widgetType);
        } catch (MustImplementInterfaceException $exception) {
            throw new InvalidArgumentException(
                sprintf('Invalid widget config hydrator implementation: %s', $exception->getMessage()),
                $exception
            );
        }
    }

    /**
     * @throws InvalidArgumentException
     */
    public function loadRepositoryByType(string $widgetType): WidgetConfigRepositoryInterface
    {
        try {
            return $this->configRepositoryLoader->loadRepository($widgetType);
        } catch (MustImplementInterfaceException $exception) {
            throw new InvalidArgumentException(
                sprintf('Invalid widget config repository implementation: %s', $exception->getMessage()),
                $exception
            );
        }
    }

    /**
     * @throws InvalidArgumentException
     *
     * @return WidgetConfigRepositoryInterface[]
     */
    private function loadRepositories(): array
    {
        try {
            return $this->configRepositoryLoader->loadRepositories();
        } catch (MustImplementInterfaceException $exception) {
            throw new InvalidArgumentException(
                sprintf('Invalid widget config implementation: %s', $exception->getMessage()),
                $exception
            );
        }
    }

    private function processRepositoryConfiguration(
        array $configData,
        string $widgetType
    ): WidgetConfig {
        $configData['widgetType'] = $widgetType;
        $hydratedConfig = $this->configListHydrator->hydrate($configData);
        $this->dispatchConfigEvent($hydratedConfig);

        return $hydratedConfig;
    }

    private function dispatchConfigEvent(WidgetConfig $config): void
    {
        $this->eventDispatcher->dispatch(new WidgetConfigEvent($config), WidgetConfigEvent::EVENT_NAME);
    }
}
