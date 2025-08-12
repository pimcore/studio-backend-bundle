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

namespace Pimcore\Bundle\StudioBackendBundle\Grid\Service;

use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Grid\Column\ClassIdInterface;
use Pimcore\Bundle\StudioBackendBundle\Grid\Column\FolderIdInterface;
use Pimcore\Bundle\StudioBackendBundle\Grid\Column\UseUserInterface;
use Pimcore\Bundle\StudioBackendBundle\Grid\Event\GridColumnConfigurationEvent;
use Pimcore\Bundle\StudioBackendBundle\Grid\Schema\ColumnConfiguration;
use Pimcore\Bundle\StudioBackendBundle\Grid\Util\ColumnFieldDefinition;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementTypes;
use Pimcore\Model\UserInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

use function array_key_exists;
use function in_array;
use function sprintf;

/**
 * @internal
 */
final readonly class ColumnConfigurationService implements ColumnConfigurationServiceInterface
{
    public function __construct(
        private GridServiceInterface $gridService,
        private EventDispatcherInterface $eventDispatcher,
    ) {
    }

    /**
     * @return ColumnConfiguration[]
     */
    public function getAvailableAssetColumnConfiguration(): array
    {
        $columns = [];
        foreach ($this->gridService->getColumnCollectors() as $collector) {
            // Only collect supported asset collectors
            if (!in_array(ElementTypes::TYPE_ASSET, $collector->supportedElementTypes(), true)) {
                continue;
            }

            // rather use the spread operator instead of array_merge in a loop
            $columns = [
                ...$columns,
                ...$collector->getColumnConfigurations($this->gridService->getColumnDefinitions()),
            ];
        }

        $this->dispatchEventForAllColumns($columns);

        return $columns;
    }

    public function getAvailableDataObjectColumnConfiguration(
        ?string $classId,
        ?int $folderId,
        UserInterface $user
    ): array {
        if (($classId === null && $folderId !== null) || ($classId !== null && $folderId === null)) {
            throw new InvalidArgumentException('Either both classId and folderId must be set or both must be null');
        }

        if ($classId === null && $folderId === null) {
            $columns = $this->getSystemDataObjectColumnConfiguration();
            $this->dispatchEventForAllColumns($columns);

            return $columns;
        }

        $columns = [];
        foreach ($this->gridService->getColumnCollectors() as $collector) {
            // Only collect supported data object collectors
            if (!in_array(ElementTypes::TYPE_DATA_OBJECT, $collector->supportedElementTypes(), true)) {
                continue;
            }

            if ($collector instanceof ClassIdInterface) {
                $collector->setClassId($classId);
            }

            if ($collector instanceof FolderIdInterface) {
                $collector->setFolderId($folderId);
            }

            if ($collector instanceof UseUserInterface) {
                $collector->setUser($user);
            }

            // rather use the spread operator instead of array_merge in a loop
            $columns = [
                ...$columns,
                ...$collector->getColumnConfigurations($this->gridService->getColumnDefinitions()),
            ];
        }

        $this->dispatchEventForAllColumns($columns);

        return $columns;

    }

    /**
     * @return ColumnConfiguration[]
     *
     * @throws InvalidArgumentException
     */
    public function getSystemDataObjectColumnConfiguration(): array
    {
        $systemCollector = $this->gridService->getColumnCollectors()['system.dataobject'];

        if (!in_array(ElementTypes::TYPE_DATA_OBJECT, $systemCollector->supportedElementTypes(), true)) {
            throw new InvalidArgumentException('collector does not support data objects');
        }

        return $systemCollector->getColumnConfigurations($this->gridService->getColumnDefinitions());
    }

    /**
     * {@inheritdoc}
     */
    public function buildDataObjectAdapterColumnConfiguration(
        ColumnFieldDefinition $definition,
        ?string $type = null,
        ?string $key = null,
        ?array $additionalConfig = null
    ): ColumnConfiguration {
        $config = [];
        $fieldDefinition = $definition->getFieldDefinition();

        $config['fieldDefinition'] = $fieldDefinition;

        if ($key === null) {
            $key = $fieldDefinition->getName();
        }

        if ($additionalConfig) {
            $config = array_merge($config, $additionalConfig);
        }

        $availableColumnDefinitions = $this->gridService->getColumnDefinitions();

        $columnDefinitionType = 'data-object.' . $definition->getFieldDefinition()->getFieldType();

        if (!array_key_exists($columnDefinitionType, $availableColumnDefinitions)) {
            throw new InvalidArgumentException(
                sprintf('Column definition type %s not found', $columnDefinitionType)
            );
        }

        return new ColumnConfiguration(
            key: $key,
            group: $definition->getGroup(),
            sortable: $availableColumnDefinitions[$columnDefinitionType]->isSortable(),
            editable: !$fieldDefinition->getNoteditable(),
            exportable: $availableColumnDefinitions[$columnDefinitionType]->isExportable(),
            filterable: $availableColumnDefinitions[$columnDefinitionType]->isFilterable(),
            localizable: $definition->isLocalized(),
            locale: null,
            type: $type,
            frontendType: $availableColumnDefinitions[$columnDefinitionType]->getFrontendType(),
            config: $config
        );
    }

    /**
     * @param ColumnConfiguration[] $columns
     */
    private function dispatchEventForAllColumns(array $columns): void
    {
        foreach ($columns as $column) {
            $this->eventDispatcher->dispatch(
                new GridColumnConfigurationEvent($column),
                GridColumnConfigurationEvent::EVENT_NAME
            );
        }
    }
}
