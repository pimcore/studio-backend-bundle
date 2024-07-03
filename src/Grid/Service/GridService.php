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

use Pimcore\Bundle\StaticResolverBundle\Models\Element\ServiceResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Grid\GridSearchInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Grid\Column\ColumnDefinitionInterface;
use Pimcore\Bundle\StudioBackendBundle\Grid\Column\ColumnResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Grid\Event\GridColumnDataEvent;
use Pimcore\Bundle\StudioBackendBundle\Grid\Event\GridColumnDefinitionEvent;
use Pimcore\Bundle\StudioBackendBundle\Grid\MappedParameter\GridParameter;
use Pimcore\Bundle\StudioBackendBundle\Grid\Schema\Column;
use Pimcore\Bundle\StudioBackendBundle\Grid\Schema\ColumnData;
use Pimcore\Bundle\StudioBackendBundle\Grid\Schema\Configuration;
use Pimcore\Bundle\StudioBackendBundle\Response\Collection;
use Pimcore\Bundle\StudioBackendBundle\Util\Traits\ElementProviderTrait;
use Pimcore\Model\DataObject\ClassDefinition;
use Pimcore\Model\Element\ElementInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @internal
 */
final class GridService implements GridServiceInterface
{
    use ElementProviderTrait;

    /**
     * @param array<int, ColumnDefinitionInterface> $columnDefinitions
     */
    private array $columnDefinitions;

    /**
     * @param array<int, ColumnResolverInterface> $columnResolvers
     */
    private array $columnResolvers;

    public function __construct(
        private readonly ColumnDefinitionLoaderInterface $columnDefinitionLoader,
        private readonly ColumnResolverLoaderInterface $columnResolverLoader,
        private readonly SystemColumnServiceInterface $systemColumnService,
        private readonly GridSearchInterface $gridSearch,
        private readonly ServiceResolverInterface $serviceResolver,
        private readonly EventDispatcherInterface $eventDispatcher
    ) {
    }

    /**
     * @throws InvalidArgumentException
     */
    public function getAssetGrid(GridParameter $gridParameter): Collection
    {
        $result = $this->gridSearch->searchAssets($gridParameter);

        if (empty($result->getIds())) {
            return new Collection(totalItems: 0, items: []);
        }

        $data = [];

        foreach ($result->getItems() as $item) {
            $type = $item->getElementType()->value;
            $asset = $this->getElement($this->serviceResolver, $type, $item->getId());

            $data[] = $this->getGridDataForElement(
                $this->getConfigurationFromArray($gridParameter->getGridConfig()),
                $asset,
                $type
            );
        }

        return new Collection(
            totalItems: $result->getPagination()->getTotalItems(),
            items: $data
        );
    }

    /**
     * @throws InvalidArgumentException
     */
    public function getGridDataForElement(
        Configuration $configuration,
        ElementInterface $element,
        string $elementType
    ): array {
        $data = [];
        foreach ($configuration->getColumns() as $column) {
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

    public function getGridValuesForElement(
        Configuration $configuration,
        ElementInterface $element,
        string $elementType
    ): array {
        $data = $this->getGridDataForElement($configuration, $element, $elementType);

        return array_map(
            static fn (ColumnData $columnData) => $columnData->getValue(),
            $data['columns']
        );
    }

    public function getAssetGridConfiguration(): Configuration
    {
        $systemColumns = $this->systemColumnService->getSystemColumnsForAssets();
        $columns = [];
        foreach ($systemColumns as $columnKey => $type) {
            if (!array_key_exists($type, $this->getColumnDefinitions())) {
                continue;
            }
            $column = new Column(
                key: $columnKey,
                group: 'system',
                sortable: $this->getColumnDefinitions()[$type]->isSortable(),
                editable: false,
                localizable: false,
                locale: null,
                type: $type,
                config: $this->getColumnDefinitions()[$type]->getConfig()
            );

            $this->eventDispatcher->dispatch(
                new GridColumnDefinitionEvent($column),
                GridColumnDefinitionEvent::EVENT_NAME
            );

            $columns[] = $column;
        }

        return new Configuration($columns);
    }

    public function getDocumentGridColumns(): Configuration
    {
        return new Configuration([]);
    }

    public function getDataObjectGridColumns(ClassDefinition $classDefinition): Configuration
    {
        return new Configuration([]);
    }

    public function getConfigurationFromArray(array $config): Configuration
    {
        $columns = [];

        foreach ($config['columns'] as $column) {
            $columns[] = new Column(
                key: $column['key'],
                group: $column['group'],
                sortable: $column['sortable'],
                editable: $column['editable'],
                localizable: $column['localizable'],
                locale: $column['locale'],
                type: $column['type'],
                config: $column['config']
            );
        }

        return new Configuration($columns);
    }

    public function getColumnKeys(Configuration $configuration): array
    {
        return array_map(
            static fn (Column $column) => $column->getKey(),
            $configuration->getColumns()
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

    private function getColumnDefinitions(): array
    {
        if ($this->columnDefinitions) {
            return $this->columnDefinitions;
        }
        $this->columnDefinitions = $this->columnDefinitionLoader->loadColumnDefinitions();

        return $this->columnDefinitions;
    }

    private function getColumnResolvers(): array
    {
        if ($this->columnResolvers) {
            return $this->columnResolvers;
        }
        $this->columnResolvers = $this->columnResolverLoader->loadColumnResolvers();

        return $this->columnResolvers;
    }
}
