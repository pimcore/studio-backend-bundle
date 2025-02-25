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
use Pimcore\Bundle\StudioBackendBundle\Element\Schema\Permissions\SaveDataObjectContextPermissions;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ElementSavingFailedException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotWriteableException;
use Pimcore\Bundle\StudioBackendBundle\Icon\Service\IconServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Perspective\Schema\SaveElementTreeWidgetConfig;
use Pimcore\Bundle\StudioBackendBundle\Perspective\Service\Loader\Widget\TaggedIteratorRepository;
use Pimcore\Bundle\StudioBackendBundle\Perspective\Service\WidgetValidationServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Perspective\Util\Constant\WidgetTypes;
use Pimcore\Config\LocationAwareConfigRepository;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use function sprintf;

/**
 * @internal
 */
#[AutoconfigureTag(TaggedIteratorRepository::REPOSITORY_TAG)]
final class ElementTreeWidgetConfigRepository implements WidgetConfigRepositoryInterface
{
    public function __construct(
        private readonly IconServiceInterface $iconService,
        private readonly NormalizerInterface $normalizer,
        private readonly WidgetValidationServiceInterface $validationService,
        private readonly array $widgetConfigurations,
        private readonly array $storageConfig,
    ) {
    }

    private ?LocationAwareConfigRepository $repository = null;

    public function getSupportedWidgetType(): string
    {
        return WidgetTypes::ELEMENT_TREE->value;
    }

    /**
     * @throws ElementSavingFailedException|NotWriteableException
     */
    public function createConfiguration(array $widgetData): string
    {
        $config = new SaveElementTreeWidgetConfig(
            $widgetData['id'],
            $widgetData['name'],
            $this->iconService->getIconForValue(),
            new SaveDataObjectContextPermissions(),
        );

        $this->saveConfigData($config);

        return $widgetData['id'];
    }

    /**
     * @throws ElementSavingFailedException|NotWriteableException
     */
    public function updateConfiguration(array $widgetData): void
    {
        $configData = $this->validationService->validateWidgetConfigData($widgetData);
        $this->saveConfigData($configData);
    }

    /**
     * @throws NotFoundException|NotWriteableException
     */
    public function getConfiguration(string $widgetId): array
    {
        [$configData, $dataSource] = $this->loadConfig($widgetId);
        $configData['isWriteable'] = $this->isRepositoryWritable($widgetId, $dataSource);
        $configData['id'] = $widgetId;

        return $configData;
    }

    /**
     * @throws ElementSavingFailedException|NotWriteableException
     */
    private function saveConfigData(SaveElementTreeWidgetConfig $widgetConfiguration): void
    {
        try {
            $widgetData = $this->normalizer->normalize($widgetConfiguration);
        } catch (Exception|ExceptionInterface $exception) {
            throw new ElementSavingFailedException(null, $exception->getMessage());
        }

        $this->isRepositoryWritable(message: 'Could not save the widget configuration: %s');

        try {
            $this->getRepository()->saveConfig($widgetConfiguration->getId(), $widgetData, function ($key, $data) {
                return [
                    Configuration::ROOT_NODE => [
                        Configuration::TREE_WIDGETS_NODE => [
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
        string $widgetId
    ): void {
        $repository = $this->getRepository();
        $this->loadConfig($widgetId);

        try {
            $repository->deleteData($widgetId, $repository->getWriteTarget());
        } catch (Exception $exception) {
            throw new NotWriteableException(
                'widget',
                sprintf(
                    'Widget configuration (%s) could not be deleted: %s',
                    $widgetId,
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
                $this->widgetConfigurations,
                Configuration::TREE_WIDGETS_NODE,
                $this->storageConfig
            );
        }

        return $this->repository;
    }

    /**
     * @throws NotFoundException
     */
    private function loadConfig(string $widgetId): array
    {
        $data = $this->getRepository()->loadConfigByKey($widgetId);
        if ($data[0] === null) {
            throw new NotFoundException(
                'widget',
                sprintf('[ID: %s, Type: %s]', $widgetId, $this->getSupportedWidgetType()),
                'ID and Type'
            );
        }

        return $data;
    }

    /**
     * @throws NotWriteableException
     */
    private function isRepositoryWritable(
        ?string $widgetId = null,
        ?string $dataSource = null,
        string $message = 'Could not export the widget configuration: %s'
    ): bool {
        try {
            return $this->getRepository()->isWriteable($widgetId, $dataSource);
        } catch (Exception $exception) {
            $message = sprintf($message, $exception->getMessage());

            throw new NotWriteableException('widget', $message, $exception);
        }
    }
}
