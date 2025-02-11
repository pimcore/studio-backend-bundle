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
            $this->iconService->getIconForWidget(),
            new SaveDataObjectContextPermissions(),
        );

        try {
            $configData = $this->normalizer->normalize($config);
        } catch (Exception|ExceptionInterface $exception) {
            throw new ElementSavingFailedException(null, $exception->getMessage());
        }

        $this->saveConfigData($widgetData['id'], $configData);

        return $widgetData['id'];
    }

    /**
     * @throws NotFoundException|NotWriteableException
     */
    public function getConfigData(string $widgetId): array
    {
        $repository = $this->getRepository();
        $data = $repository->loadConfigByKey($widgetId);
        [$configData, $dataSource] = $data;
        if ($configData === null) {
            throw new NotFoundException('Element Tree Widget', $widgetId);
        }

        $configData['isWriteable'] = $this->isRepositoryWritable($widgetId, $dataSource);
        $configData['id'] = $widgetId;

        return $configData;
    }

    /**
     * @throws ElementSavingFailedException|NotWriteableException
     */
    public function saveConfigData(string $configId, array $widgetData): void
    {
        $this->isRepositoryWritable(message: 'Could not save the widget configuration: %s');

        try {
            $this->getRepository()->saveConfig($configId, $widgetData, function ($key, $data) {
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
            $configurations[] = $this->getConfigData($key);
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
                'widget',
                sprintf(
                    'Widget configuration (%s) could not be deleted: %s',
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
                $this->widgetConfigurations,
                Configuration::TREE_WIDGETS_NODE,
                $this->storageConfig
            );
        }

        return $this->repository;
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
