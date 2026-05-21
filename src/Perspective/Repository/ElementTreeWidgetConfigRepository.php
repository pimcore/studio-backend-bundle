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

namespace Pimcore\Bundle\StudioBackendBundle\Perspective\Repository;

use Exception;
use Pimcore\Bundle\StudioBackendBundle\DependencyInjection\Configuration;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ElementSavingFailedException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotWriteableException;
use Pimcore\Bundle\StudioBackendBundle\Icon\Service\IconServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Perspective\Schema\SaveElementTreeWidgetConfig;
use Pimcore\Bundle\StudioBackendBundle\Perspective\Service\Loader\Widget\TaggedIteratorRepository;
use Pimcore\Bundle\StudioBackendBundle\Perspective\Service\Widget\TreeContextPermissionsServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Perspective\Service\WidgetValidationServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Perspective\Util\Constant\ElementTreeWidgets;
use Pimcore\Bundle\StudioBackendBundle\Perspective\Util\Constant\Perspectives;
use Pimcore\Bundle\StudioBackendBundle\Perspective\Util\Constant\WidgetTypes;
use Pimcore\Bundle\StudioBackendBundle\Util\Config\ConfigKeyMapperInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementTypes;
use Pimcore\Config\LocationAwareConfigRepository;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use function in_array;
use function sprintf;

/**
 * @internal
 */
#[AutoconfigureTag(TaggedIteratorRepository::REPOSITORY_TAG)]
final class ElementTreeWidgetConfigRepository implements WidgetConfigRepositoryInterface
{
    public function __construct(
        private readonly ConfigKeyMapperInterface $configKeyMapper,
        private readonly IconServiceInterface $iconService,
        private readonly NormalizerInterface $normalizer,
        private readonly TreeContextPermissionsServiceInterface $contextPermissionService,
        private readonly WidgetValidationServiceInterface $validationService,
        private readonly array $widgetConfigurations,
        private readonly array $storageConfig,
        private readonly array $defaultPerspective
    ) {
    }

    private ?LocationAwareConfigRepository $repository = null;

    public function getSupportedWidgetType(): string
    {
        return WidgetTypes::ELEMENT_TREE->value;
    }

    public function isWidgetTypeOnlyWrapper(): bool
    {
        return false;
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
            $this->contextPermissionService->list(ElementTypes::TYPE_DATA_OBJECT, []),
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
        if (in_array($widgetId, ElementTreeWidgets::values(), true)) {
            return $this->defaultPerspective[Perspectives::DEFAULT_ID->value]['widgetsLeft'][$widgetId];
        }

        [$configData, $dataSource] = $this->loadConfig($widgetId);
        $configData = $this->configKeyMapper->mapKeysForApp($configData);
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
            $snakeCaseData = $this->configKeyMapper->mapKeysForConfig($widgetData);
            $this->getRepository()->saveConfig(
                $widgetConfiguration->getId(),
                $snakeCaseData,
                function ($key, $data) {
                    return [
                        Configuration::ROOT_NODE => [
                            Configuration::TREE_WIDGETS_NODE => [
                                $key => $data,
                            ],
                        ],
                    ];
                }
            );
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
        $keys = array_merge(ElementTreeWidgets::values(), $this->getRepository()->fetchAllKeys());
        foreach ($keys as $key) {
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
