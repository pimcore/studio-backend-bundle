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
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\ClassDefinitionResolverInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\Element\ServiceResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Grid\GridSearchInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\SearchResult\SearchResultItemInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Grid\Column\ColumnCollectorInterface;
use Pimcore\Bundle\StudioBackendBundle\Grid\Column\ColumnDefinitionInterface;
use Pimcore\Bundle\StudioBackendBundle\Grid\Column\ColumnResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Grid\Column\CoreElementColumnResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Grid\Column\StudioElementColumnResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Grid\Event\GridColumnDataEvent;
use Pimcore\Bundle\StudioBackendBundle\Grid\MappedParameter\GridParameter;
use Pimcore\Bundle\StudioBackendBundle\Grid\Schema\Column;
use Pimcore\Bundle\StudioBackendBundle\Grid\Schema\ColumnData;
use Pimcore\Bundle\StudioBackendBundle\Grid\Util\Collection\ColumnCollection;
use Pimcore\Bundle\StudioBackendBundle\Response\Collection;
use Pimcore\Bundle\StudioBackendBundle\Response\StudioElementInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementTypes;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\ElementProviderTrait;
use Pimcore\Model\DataObject\ClassDefinition;
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
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly ServiceResolverInterface $serviceResolver,
        private readonly ClassDefinitionResolverInterface $classDefinitionResolver
    ) {
    }

    /**
     * @throws InvalidArgumentException
     */
    public function getAssetGrid(GridParameter $gridParameter): Collection
    {
        $result = $this->gridSearch->searchAssets($gridParameter);

        return $this->getCollectionFromSearchResult($result, $gridParameter, ElementTypes::TYPE_ASSET);
    }

    /**
     * @throws NotFoundException
     * @throws Exception
     */
    public function getDataObjectGrid(GridParameter $gridParameter, string $classId): Collection
    {
        $filter = $gridParameter->getFilters();
        $classDefinition = $this->classDefinitionResolver->getById($classId);

        if (!$classDefinition instanceof ClassDefinition) {
            throw new NotFoundException('Class definition', $classId);
        }

        $filter->setClassName($classDefinition->getName());

        $result = $this->gridSearch->searchDataObjects($gridParameter);

        return $this->getCollectionFromSearchResult($result, $gridParameter, ElementTypes::TYPE_OBJECT);
    }

    /**
     * @throws InvalidArgumentException
     */
    public function getGridDataForElement(
        ColumnCollection $columnCollection,
        StudioElementInterface $element,
        string $elementType
    ): array {
        $data = [];

        $databaseElement = null;
        if ($elementType === ElementTypes::TYPE_OBJECT) {
            $databaseElement = $this->getElement(
                $this->serviceResolver,
                $elementType,
                $element->getId()
            );
        }

        foreach ($columnCollection->getColumns() as $column) {
            // move this to the resolver
            if (!$this->supports($column, $elementType)) {
                continue;
            }

            $resolver = $this->getColumnResolvers()[$column->getType()];

            $columnData = match (true) {
                $databaseElement && $resolver instanceof CoreElementColumnResolverInterface =>
                    $resolver->resolveForCoreElement($column, $databaseElement),
                $resolver instanceof StudioElementColumnResolverInterface =>
                    $resolver->resolveForStudioElement($column, $element),
                default =>
                    throw new InvalidArgumentException(
                        'Resolver must implement either StudioElementColumnResolverInterface or
                        CoreElementColumnResolverInterface'
                    ),
            };

            $this->eventDispatcher->dispatch(
                new GridColumnDataEvent($columnData),
                GridColumnDataEvent::EVENT_NAME
            );

            $data['id'] = $element->getId();
            $data['columns'][] = $columnData;
            $data['isLocked'] = $element->getIsLocked();
            $data['permissions'] = $element->getPermissions();
        }

        return $data;
    }

    /**
     * @throws InvalidArgumentException
     */
    public function getGridValuesForElement(
        ColumnCollection $columnCollection,
        StudioElementInterface $element,
        string $elementType
    ): array {
        $data = $this->getGridDataForElement($columnCollection, $element, $elementType);

        return array_map(
            static fn (ColumnData $columnData) => $columnData->getValue(),
            $data['columns']
        );
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
                if (!$column->getGroup()) {
                    throw new InvalidArgumentException('Group must be set when withGroup is true');
                }

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
    public function getColumnDefinitions(): array
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
    public function getColumnCollectors(): array
    {
        if ($this->columnCollectors) {
            return $this->columnCollectors;
        }

        $this->columnCollectors = $this->columnCollectorLoader->loadColumnCollectors();

        return $this->columnCollectors;
    }

    /**
     * @return array<string, ColumnResolverInterface>
     */
    public function getColumnResolvers(): array
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

    private function getCollectionFromSearchResult(
        SearchResultItemInterface $searchResultItem,
        GridParameter $gridParameter,
        string $elementType
    ): Collection {
        $items = $searchResultItem->getItems();

        if (empty($items)) {
            return new Collection(totalItems: 0, items: []);
        }

        $data = [];
        foreach ($items as $item) {
            $data[] = $this->getGridDataForElement(
                $this->getConfigurationFromArray($gridParameter->getColumns()),
                $item,
                $elementType
            );
        }

        return new Collection(
            totalItems: $searchResultItem->getTotalItems(),
            items: $data
        );
    }
}
