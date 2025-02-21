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

namespace Pimcore\Bundle\StudioBackendBundle\Perspective\Repository;

use Exception;
use Pimcore\Bundle\StudioBackendBundle\DependencyInjection\Configuration;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ElementSavingFailedException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotWriteableException;
use Pimcore\Bundle\StudioBackendBundle\Icon\Service\IconServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Perspective\Schema\SavePerspectiveConfig;
use Pimcore\Bundle\StudioBackendBundle\Perspective\Service\PerspectiveValidationServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Perspective\Util\Constant\Perspectives;
use Pimcore\Config\LocationAwareConfigRepository;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use function sprintf;

/**
 * @internal
 */
final class PerspectiveConfigRepository implements PerspectiveConfigRepositoryInterface
{
    public function __construct(
        private readonly IconServiceInterface $iconService,
        private readonly NormalizerInterface $normalizer,
        private readonly PerspectiveValidationServiceInterface $validationService,
        private readonly array $perspectiveConfigurations,
        private readonly array $storageConfig,
        private readonly array $defaultPerspective
    ) {
    }

    private ?LocationAwareConfigRepository $repository = null;

    /**
     * @throws ElementSavingFailedException|NotWriteableException
     */
    public function createConfiguration(array $perspectiveData): string
    {
        $config = new SavePerspectiveConfig(
            $perspectiveData['id'],
            $perspectiveData['name'],
            $this->iconService->getIconForValue()
        );

        $this->saveConfigData($config);

        return $perspectiveData['id'];
    }

    /**
     * @throws ElementSavingFailedException|NotWriteableException
     */
    public function updateConfiguration(array $perspectiveData): void
    {
        $configData = $this->validationService->validatePerspectiveConfigData($perspectiveData);
        $this->saveConfigData($configData);
    }

    /**
     * @throws NotFoundException|NotWriteableException
     */
    public function getConfiguration(string $perspectiveId): array
    {
        if ($perspectiveId === Perspectives::DEFAULT_ID->value) {
            return $this->defaultPerspective[Perspectives::DEFAULT_ID->value];
        }

        $repository = $this->getRepository();
        $data = $repository->loadConfigByKey($perspectiveId);
        [$configData, $dataSource] = $data;
        if ($configData === null) {
            throw new NotFoundException('Perspective', $perspectiveId);
        }

        $configData['isWriteable'] = $this->isRepositoryWritable($perspectiveId, $dataSource);
        $configData['id'] = $perspectiveId;

        return $configData;
    }

    /**
     * @throws ElementSavingFailedException|NotWriteableException
     */
    private function saveConfigData(SavePerspectiveConfig $perspectiveConfig): void
    {
        try {
            $perspective = $this->normalizer->normalize($perspectiveConfig);
        } catch (Exception|ExceptionInterface $exception) {
            throw new ElementSavingFailedException(null, $exception->getMessage());
        }

        $this->isRepositoryWritable(message: 'Could not save the perspective configuration: %s');

        try {
            $this->getRepository()->saveConfig($perspectiveConfig->getId(), $perspective, function ($key, $data) {
                return [
                    Configuration::ROOT_NODE => [
                        Configuration::PERSPECTIVES_NODE => [
                            $key => $data,
                        ],
                    ],
                ];
            });
        } catch (Exception $exception) {
            throw new ElementSavingFailedException(null, $exception->getMessage());
        }
    }

    /**
     * @throws NotFoundException|NotWriteableException
     */
    public function listConfigurations(): array
    {
        $configurations = [];
        foreach ($this->getRepository()->fetchAllKeys() as $key) {
            $configurations[] = $this->getConfiguration($key);
        }

        return $configurations;
    }

    /**
     * @throws NotWriteableException
     */
    public function deleteConfiguration(
        string $configId
    ): void {
        $repository = $this->getRepository();

        try {
            $repository->deleteData($configId, $repository->getWriteTarget());
        } catch (Exception $exception) {
            throw new NotWriteableException(
                'perspective',
                sprintf(
                    'Perspective configuration (%s) could not be deleted: %s',
                    $configId,
                    $exception->getMessage()
                ),
                $exception
            );
        }
    }

    private function getRepository(): LocationAwareConfigRepository
    {
        if (!$this->repository) {
            $this->repository = new LocationAwareConfigRepository(
                $this->perspectiveConfigurations,
                Configuration::PERSPECTIVES_NODE,
                $this->storageConfig
            );
        }

        return $this->repository;
    }

    /**
     * @throws NotWriteableException
     */
    private function isRepositoryWritable(
        ?string $perspectiveId = null,
        ?string $dataSource = null,
        string $message = 'Could not export the perspective configuration: %s'
    ): bool {
        try {
            return $this->getRepository()->isWriteable($perspectiveId, $dataSource);
        } catch (Exception $exception) {
            $message = sprintf($message, $exception->getMessage());

            throw new NotWriteableException('perspective', $message, $exception);
        }
    }
}
