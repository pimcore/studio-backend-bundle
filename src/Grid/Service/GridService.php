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

use Exception;
use Pimcore\Bundle\StaticResolverBundle\Models\Element\ServiceResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Grid\GridSearchInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Grid\Column\ColumnCollectorInterface;
use Pimcore\Bundle\StudioBackendBundle\Grid\Column\ColumnDefinitionInterface;
use Pimcore\Bundle\StudioBackendBundle\Grid\Column\ColumnResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Grid\Event\GridColumnConfigurationEvent;
use Pimcore\Bundle\StudioBackendBundle\Grid\Event\GridColumnDataEvent;
use Pimcore\Bundle\StudioBackendBundle\Grid\MappedParameter\GridParameter;
use Pimcore\Bundle\StudioBackendBundle\Grid\Schema\Column;
use Pimcore\Bundle\StudioBackendBundle\Grid\Schema\ColumnConfiguration;
use Pimcore\Bundle\StudioBackendBundle\Grid\Schema\ColumnData;
use Pimcore\Bundle\StudioBackendBundle\Grid\Util\Collection\ColumnCollection;
use Pimcore\Bundle\StudioBackendBundle\Response\Collection;
use Pimcore\Bundle\StudioBackendBundle\Util\Constants\ElementTypes;
use Pimcore\Bundle\StudioBackendBundle\Util\Traits\ElementProviderTrait;
use Pimcore\Model\DataObject\ClassDefinition;
use Pimcore\Model\Element\ElementInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use function array_key_exists;
use function in_array;

/**
 * @internal
 */
final class GridService implements GridServiceInterface
{
    use ElementProviderTrait;

    /**
     * @param array<int, ColumnDefinitionInterface> $columnDefinitions
     */
    private array $columnDefinitions = [];

    /**
     * @param array<int, ColumnResolverInterface> $columnResolvers
     */
    private array $columnResolvers = [];

    /**
     * @param array<string, ColumnCollectorInterface> $columnCollectors
     */
    private array $columnCollectors = [];

    public function __construct(
        private readonly ColumnDefinitionLoaderInterface $columnDefinitionLoader,
        private readonly ColumnResolverLoaderInterface $columnResolverLoader,
        private readonly ColumnCollectorLoaderInterface $columnCollectorLoader,
        private readonly GridSearchInterface $gridSearch,
        private readonly ServiceResolverInterface $serviceResolver,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {
    }

    /**
     * @throws InvalidArgumentException
     */
    public function getAssetGrid(GridParameter $gridParameter): Collection
    {
        $result = $this->gridSearch->searchAssets($gridParameter);
        $items = $result->getItems();

        if (empty($items)) {
            return new Collection(totalItems: 0, items: []);
        }

        $data = [];
        foreach ($items as $item) {
            $asset = $this->getElement($this->serviceResolver, 'asset', $item->getId());
            $data[] = $this->getGridDataForElement(
                $this->getConfigurationFromArray($gridParameter->getColumns()),
                $asset,
                ElementTypes::TYPE_ASSET
            );
        }

        return new Collection(
            totalItems: $result->getTotalItems(),
            items: $data
        );
    }

    /**
     * @throws InvalidArgumentException
     */
    public function getGridDataForElement(
        ColumnCollection $columnCollection,
        ElementInterface $element,
        string $elementType
    ): array {
        $data = [];
        foreach ($columnCollection->getColumns() as $column) {
            // move this to the resolver
            if (!$this->supports($column, $elementType)) {
                continue;
            }

            $columnData = $this->getColumnResolvers()[$column->getType()]->resolve($column, $element);

            $this->eventDispatcher->dispatch(
                new GridColumnDataEvent($columnData),
                GridColumnDataEvent::EVENT_NAME
            );

            $data['columns'][] = $columnData;
        }

        return $data;
    }

    /**
     * @throws InvalidArgumentException
     */
    public function getGridValuesForElement(
        ColumnCollection $columnCollection,
        ElementInterface $element,
        string $elementType
    ): array {
        $data = $this->getGridDataForElement($columnCollection, $element, $elementType);
        return array_map(
            static fn (ColumnData $columnData) => $columnData->getValue(),
            $data['columns']
        );
    }

    /**
     * @return ColumnConfiguration[]
     */
    public function getAssetGridConfiguration(): array
    {
        $columns = [];
        foreach ($this->getColumnCollectors() as $collector) {
            // Only collect supported asset collectors
            if (!in_array(ElementTypes::TYPE_ASSET, $collector->supportedElementTypes(), true)) {
                continue;
            }

            $columns = array_merge(
                $columns,
                $collector->getColumnConfigurations(
                    $this->getColumnDefinitions()
                )
            );
        }

        foreach ($columns as $column) {
            $this->eventDispatcher->dispatch(
                new GridColumnConfigurationEvent($column),
                GridColumnConfigurationEvent::EVENT_NAME
            );
        }

        return $columns;
    }

    public function getDocumentGridColumns(): ColumnCollection
    {
        return new ColumnCollection([]);
    }

    public function getDataObjectGridColumns(ClassDefinition $classDefinition): ColumnCollection
    {
        return new ColumnCollection([]);
    }

    /**
     * @throws InvalidArgumentException
     */
    public function getConfigurationFromArray(array $config, bool $isExport = false): ColumnCollection
    {
        $columns = [];
        foreach ($config as $column) {
            if ($isExport && !$this->isExportable($column['type'])) {
                continue;
            }

            try {
                $columns[] = new Column(
                    key: $column['key'],
                    locale: $column['locale'] ?? null,
                    type: $column['type'],
                    group: $column['group'] ?? null,
                    config: $column['config']
                );
            } catch (Exception) {
                throw new InvalidArgumentException('Invalid column configuration');
            }
        }

        return new ColumnCollection($columns);
    }

    public function getColumnKeys(ColumnCollection $columnCollection, bool $withGroup = false): array
    {
        return array_map(
            static function (Column $column) use ($withGroup) {
                return $column->getKey() . ($withGroup ? '~' . $column->getGroup() : '');
            },
            $columnCollection->getColumns()
        );
    }

    private function supports(Column $column, string $elementType): bool
    {
        if (!array_key_exists($column->getType(), $this->getColumnResolvers())) {
            return false;
        }

        /** @var ColumnResolverInterface $resolver */
        $resolver = $this->getColumnResolvers()[$column->getType()];

        if (!in_array($elementType, $resolver->supportedElementTypes(), true)) {
            return false;
        }

        return true;
    }

    /**
     * @return array<string, ColumnDefinitionInterface>
     */
    private function getColumnDefinitions(): array
    {
        if ($this->columnDefinitions) {
            return $this->columnDefinitions;
        }
        $this->columnDefinitions = $this->columnDefinitionLoader->loadColumnDefinitions();

        return $this->columnDefinitions;
    }

    /**
     * @return array<string, ColumnCollectorInterface>
     */
    private function getColumnCollectors(): array
    {
        if ($this->columnCollectors) {
            return $this->columnCollectors;
        }

        $this->columnCollectors = $this->columnCollectorLoader->loadColumnCollectors();

        return $this->columnCollectors;
    }

    private function getColumnResolvers(): array
    {
        if ($this->columnResolvers) {
            return $this->columnResolvers;
        }
        $this->columnResolvers = $this->columnResolverLoader->loadColumnResolvers();

        return $this->columnResolvers;
    }

    private function isExportable(string $type): bool
    {
        if (!array_key_exists($type, $this->getColumnDefinitions())) {
            return false;
        }

        return $this->getColumnDefinitions()[$type]->isExportable();
    }
}
