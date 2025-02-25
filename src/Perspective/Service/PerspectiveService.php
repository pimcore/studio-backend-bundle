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

use Exception;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ElementSavingFailedException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotWriteableException;
use Pimcore\Bundle\StudioBackendBundle\Icon\Service\IconServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Perspective\Event\PerspectiveConfigEvent;
use Pimcore\Bundle\StudioBackendBundle\Perspective\Hydrator\PerspectiveConfigDetailHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\Perspective\Hydrator\PerspectiveConfigHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\Perspective\Repository\PerspectiveConfigRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\Perspective\Schema\AddPerspectiveConfig;
use Pimcore\Bundle\StudioBackendBundle\Perspective\Schema\PerspectiveConfig;
use Pimcore\Bundle\StudioBackendBundle\Perspective\Schema\PerspectiveConfigDetail;
use Pimcore\Bundle\StudioBackendBundle\Perspective\Schema\SavePerspectiveConfig;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\ValidateConfigurationTrait;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Uid\Factory\UuidFactory;

/**
 * @internal
 */
final readonly class PerspectiveService implements PerspectiveServiceInterface
{
    use ValidateConfigurationTrait;

    public function __construct(
        private EventDispatcherInterface $eventDispatcher,
        private IconServiceInterface $iconService,
        private NormalizerInterface $normalizer,
        private PerspectiveConfigHydratorInterface $configHydrator,
        private PerspectiveConfigDetailHydratorInterface $configDetailHydrator,
        private PerspectiveConfigRepositoryInterface $configRepository,
        private PerspectiveValidationServiceInterface $validationService,
        private UuidFactory $uuidFactory
    ) {
    }

    /**
     * @throws ElementSavingFailedException|InvalidArgumentException|NotFoundException|NotWriteableException
     */
    public function addConfig(AddPerspectiveConfig $config): string
    {
        $perspectiveId = $this->getValidConfigId($this->uuidFactory);
        $perspectiveData = $this->createPerspectiveData(
            new SavePerspectiveConfig($config->getName(), $this->iconService->getIconForValue())
        );
        $this->configRepository->saveConfiguration($perspectiveId, $perspectiveData);

        return $perspectiveId;
    }

    /**
     * @throws ElementSavingFailedException|InvalidArgumentException|NotFoundException|NotWriteableException
     */
    public function updateConfig(string $perspectiveId, SavePerspectiveConfig $config): void
    {
        $this->configRepository->getConfiguration($perspectiveId);
        $this->validationService->validateWidgets($config);
        $this->validationService->validateExpandedWidgets($config);

        $perspectiveData = $this->createPerspectiveData($config);
        $perspectiveData['contextPermissions'] = $this->validationService->getValidContextPermissions(
            $config->getContextPermissions()
        );

        $this->configRepository->saveConfiguration($perspectiveId, $perspectiveData);
    }

    /**
     * @throws InvalidArgumentException|NotFoundException|NotWriteableException
     */
    public function getConfigData(string $perspectiveId): PerspectiveConfigDetail
    {
        $configData = $this->configRepository->getConfiguration($perspectiveId);
        $hydrated = $this->configDetailHydrator->hydrate($configData);
        $this->dispatchEvent($hydrated);

        return $hydrated;
    }

    /**
     * @throws NotFoundException|NotWriteableException
     *
     * @return PerspectiveConfig[]
     */
    public function listConfigurations(): array
    {
        $perspectives = [];
        foreach ($this->configRepository->listConfigurations() as $configData) {
            $hydrated = $this->configHydrator->hydrate($configData);
            $this->dispatchEvent($hydrated);

            $perspectives[] = $hydrated;
        }

        return $perspectives;
    }

    /**
     * @throws NotWriteableException
     */
    public function deleteConfig(string $perspectiveId): void
    {
        $this->configRepository->deleteConfiguration($perspectiveId);
    }

    /**
     * @throws ElementSavingFailedException|InvalidArgumentException
     */
    private function createPerspectiveData(SavePerspectiveConfig $perspectiveConfig): array
    {
        try {
            $perspective = $this->normalizer->normalize($perspectiveConfig);
        } catch (Exception|ExceptionInterface $exception) {
            throw new ElementSavingFailedException(null, $exception->getMessage());
        }
        $perspective['name'] = $this->getValidConfigName($perspective);

        return $perspective;
    }

    /**
     * @throws InvalidArgumentException
     */
    private function dispatchEvent(PerspectiveConfig $config): void
    {
        $this->eventDispatcher->dispatch(
            new PerspectiveConfigEvent($config),
            PerspectiveConfigEvent::EVENT_NAME
        );
    }
}
