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

namespace Pimcore\Bundle\StudioBackendBundle\Grid\Service;

use Pimcore\Bundle\StudioBackendBundle\Grid\Column\ClassIdInterface;
use Pimcore\Bundle\StudioBackendBundle\Grid\Column\FolderIdInterface;
use Pimcore\Bundle\StudioBackendBundle\Grid\Event\GridColumnConfigurationEvent;
use Pimcore\Bundle\StudioBackendBundle\Grid\Schema\ColumnConfiguration;
use Pimcore\Bundle\StudioBackendBundle\Grid\Util\ColumnFieldDefinition;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementTypes;
use Pimcore\Model\DataObject\ClassDefinition\Data\Select;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

use function in_array;

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

    public function getAvailableDataObjectColumnConfiguration(string $classId, int $folderId): array
    {
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

            // rather use the spread operator instead of array_merge in a loop
            $columns = [
                ...$columns,
                ...$collector->getColumnConfigurations($this->gridService->getColumnDefinitions()),
            ];
        }

        $this->dispatchEventForAllColumns($columns);

        return $columns;

    }

    public function buildColumnConfiguration(ColumnFieldDefinition $definition): ColumnConfiguration
    {
        $options = null;
        $fieldDefinition = $definition->getFieldDefinition();
        if ($fieldDefinition instanceof Select) {
            $options = $fieldDefinition->getOptions();
        }

        return new ColumnConfiguration(
            key: $fieldDefinition->getName(),
            group: $definition->getGroup(),
            sortable: true,
            editable: !$fieldDefinition->getNoteditable(),
            localizable: $definition->isLocalized(),
            locale: null,
            type: 'dataobject.' . $fieldDefinition->getFieldType(),
            frontendType: $fieldDefinition->getFieldType(),
            config: $options ? ['options' => $options] : [],
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
