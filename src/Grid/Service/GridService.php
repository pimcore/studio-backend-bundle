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

use Exception;
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\ClassDefinitionResolverInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\LocalizedFieldResolverInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\Element\ServiceResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Grid\GridSearchInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\SearchResult\SearchResultItemInterface;
use Pimcore\Bundle\StudioBackendBundle\Element\Schema\Permissions;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Util\Config;
use Pimcore\Bundle\StudioBackendBundle\Filter\MappedParameter\FilterParameter;
use Pimcore\Bundle\StudioBackendBundle\Grid\Column\ColumnCollectorInterface;
use Pimcore\Bundle\StudioBackendBundle\Grid\Column\ColumnDefinitionInterface;
use Pimcore\Bundle\StudioBackendBundle\Grid\Column\ColumnResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Grid\Column\CoreElementColumnResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Grid\Column\ExportResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Grid\Column\StudioElementColumnResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Grid\Event\GridColumnDataEvent;
use Pimcore\Bundle\StudioBackendBundle\Grid\MappedParameter\AdvancedColumnPreviewParameter;
use Pimcore\Bundle\StudioBackendBundle\Grid\MappedParameter\GridParameter;
use Pimcore\Bundle\StudioBackendBundle\Grid\Schema\Column;
use Pimcore\Bundle\StudioBackendBundle\Grid\Schema\ColumnConfiguration;
use Pimcore\Bundle\StudioBackendBundle\Grid\Schema\ColumnData;
use Pimcore\Bundle\StudioBackendBundle\Grid\Util\Collection\ColumnCollection;
use Pimcore\Bundle\StudioBackendBundle\Response\Collection;
use Pimcore\Bundle\StudioBackendBundle\Response\StudioElementInterface;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementPermissions;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementTypes;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\ElementProviderTrait;
use Pimcore\Model\DataObject\ClassDefinition;
use Pimcore\Model\Element\ElementInterface;
use Pimcore\Model\UserInterface;
use Psr\Log\LoggerInterface;
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
        private readonly SecurityServiceInterface $securityService,
        private readonly ServiceResolverInterface $serviceResolver,
        private readonly ClassDefinitionResolverInterface $classDefinitionResolver,
        private readonly LocalizedFieldResolverInterface $localizedFieldResolver,
        private readonly WorkflowPermissionMergerInterface $workflowPermissionMerger,
        private readonly LoggerInterface $pimcoreLogger,
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function getAssetGrid(GridParameter $gridParameter): Collection
    {
        $result = $this->gridSearch->searchAssets($gridParameter);

        return $this->getCollectionFromSearchResult(
            $result,
            $gridParameter,
            ElementTypes::TYPE_ASSET
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getDocumentGrid(GridParameter $gridParameter): Collection
    {
        $result = $this->gridSearch->searchDocuments($gridParameter);

        return $this->getCollectionFromSearchResult(
            $result,
            $gridParameter,
            ElementTypes::TYPE_DOCUMENT
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getDataObjectGrid(
        GridParameter $gridParameter,
        ?string $classId
    ): Collection {
        $filter = $gridParameter->getFilters();

        if ($classId) {
            $classDefinition = $this->classDefinitionResolver->getById($classId);

            if (!$classDefinition instanceof ClassDefinition) {
                throw new NotFoundException('Class definition', $classId);
            }

            $filter->setClassName($classDefinition->getName());
        }

        $result = $this->gridSearch->searchDataObjects($gridParameter);

        $fallbackBackup = $this->localizedFieldResolver->getGetFallbackValues();
        $this->localizedFieldResolver->setGetFallbackValues($gridParameter->getApplyFallbackLanguages());

        try {
            return $this->getCollectionFromSearchResult(
                $result,
                $gridParameter,
                ElementTypes::TYPE_OBJECT
            );
        } finally {
            $this->localizedFieldResolver->setGetFallbackValues($fallbackBackup);
        }
    }

    /**
     * @throws Exception
     */
    public function getPreviewOfAdvancedColumn(AdvancedColumnPreviewParameter $parameter): ColumnData
    {
        $filter = new FilterParameter(
            columnFilters: [
                [
                    'type' => 'system.id',
                    'filterValue' => $parameter->getObjectId(),
                ],
            ]
        );

        $gridParameter = new GridParameter(
            folderId: 1,
            columns: [$parameter->getColumn()],
            filters: $filter
        );

        $gridData = $this->getDataObjectGrid($gridParameter, null);

        if (!isset($gridData->getItems()[0]['columns'][0])) {
            throw new NotFoundException('Data object', $parameter->getObjectId());
        }

        return $gridData->getItems()[0]['columns'][0];
    }

    /**
     * {@inheritdoc}
     */
    public function getGridDataForElement(
        ColumnCollection $columnCollection,
        ?StudioElementInterface $element,
        string $elementType,
        int $elementId,
        bool $isExport = false,
        ?UserInterface $user = null,
    ): array {
        $data = [];

        $databaseElement = null;
        if ($isExport || $elementType === ElementTypes::TYPE_OBJECT) {
            $databaseElement = $this->getElement($this->serviceResolver, $elementType, $elementId);
            if ($user !== null) {
                $this->securityService->hasElementPermission(
                    $databaseElement,
                    $user,
                    ElementPermissions::VIEW_PERMISSION
                );
            }
        }

        if ($isExport && $databaseElement->getType() === ElementTypes::TYPE_FOLDER) {
            throw new InvalidArgumentException(
                message: 'Exporting folder elements is not supported',
                errorKey: Config::ELEMENT_FOLDER_COLLECTION_NOT_SUPPORTED->value
            );
        }

        $permissions = $this->resolvePermissions($element, $databaseElement, $elementType, $elementId);

        foreach ($columnCollection->getColumns() as $column) {
            // move this to the resolver
            if (!$this->supports($column, $elementType)) {
                continue;
            }

            $resolver = $this->getColumnResolvers()[$column->getType()];

            $columnData = match (true) {
                $databaseElement && $isExport && $user && $resolver instanceof ExportResolverInterface =>
                    $resolver->resolveForExport($column, $databaseElement, $user),
                $databaseElement && $resolver instanceof CoreElementColumnResolverInterface =>
                    $resolver->resolveForCoreElement($column, $databaseElement),
                $element !== null && $resolver instanceof StudioElementColumnResolverInterface =>
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

            $data['id'] = $elementId;
            $data['columns'][] = $columnData;
            $data['isLocked'] = $element?->getIsLocked();
            $data['permissions'] = $permissions;
        }

        return $data;
    }

    /**
     * Resolves the grid row permissions, further restricting the workspace/user permissions by the
     * element's current workflow place permissions when the element has a workflow with permissions.
     */
    private function resolvePermissions(
        ?StudioElementInterface $element,
        ?ElementInterface $databaseElement,
        string $elementType,
        int $elementId,
    ): ?Permissions {
        $permissions = $element?->getPermissions();

        if ($permissions === null || $element === null || !$element->getHasWorkflowWithPermissions()) {
            return $permissions;
        }

        $workflowElement = $databaseElement ?? $this->getElement($this->serviceResolver, $elementType, $elementId);

        return $this->workflowPermissionMerger->mergeWorkflowPermissions($permissions, $workflowElement);
    }

    /**
     * @throws InvalidArgumentException
     */
    public function getGridValuesForElement(
        ColumnCollection $columnCollection,
        string $elementType,
        int $elementId,
        UserInterface $user
    ): array {

        $data = $this->getGridDataForElement($columnCollection, null, $elementType, $elementId, true, $user);

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
    public function getConfigurationForExport(
        array $config,
        array $columnsDefinitions
    ): ColumnCollection {
        $exportableColumns = [];
        foreach ($config as $column) {
            $key = $column['key'];
            if ($column['type'] === 'dataobject.advanced') {
                $key = 'advanced';
            }

            $columnConfig = $this->findColumnConfiguration($key, $columnsDefinitions);
            if (!$columnConfig || !$columnConfig->isExportable()) {
                continue;
            }

            $exportableColumns[] = $column;
        }

        return $this->getConfigurationFromArray($exportableColumns);
    }

    public function getColumnKeys(ColumnCollection $columnCollection, bool $withGroup = false): array
    {
        return array_map(
            function (Column $column) use ($withGroup) {
                if ($withGroup === true && !$column->getGroup()) {
                    throw new InvalidArgumentException('Group must be set when withGroup is true');
                }
                $firstGroup = $column->getGroup()[0] ?? '';
                $fd = $column->getConfig()['fieldDefinition'] ?? null;

                if ($withGroup) {
                    $key = $fd ? ($fd['name'] ?? $column->getKey()) : $column->getKey();
                } else {
                    $key = $fd ? ($fd['title'] ?? $column->getKey()) : $column->getKey();
                }

                $key = $this->appendLocale($key, $column);

                return ($withGroup ? $firstGroup . '~' : '') . $key;
            },
            $columnCollection->getColumns()
        );
    }

    private function appendLocale(string $key, Column $column): string
    {
        $locale = $column->getLocale();
        if ($locale !== null) {
            return $key . ' (' . $locale . ')';
        }

        return $key;
    }

    /**
     * @throws InvalidArgumentException
     */
    private function getConfigurationFromArray(
        array $config
    ): ColumnCollection {

        $columns = [];
        foreach ($config as $column) {
            try {
                $columns[] = new Column(
                    key: $column['key'],
                    locale: $column['locale'] ?? null,
                    type: $column['type'],
                    group: $column['group'] ?? null,
                    config: $column['config'],
                    width: $column['width'] ?? null
                );
            } catch (Exception) {
                throw new InvalidArgumentException('Invalid column configuration');
            }
        }

        return new ColumnCollection($columns);
    }

    private function supports(Column $column, string $elementType): bool
    {
        if (!array_key_exists($column->getType(), $this->getColumnResolvers())) {
            return false;
        }

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
        $columns = $this->getConfigurationFromArray($gridParameter->getColumns());
        foreach ($items as $item) {
            try {
                $data[] = $this->getGridDataForElement(
                    $columns,
                    $item,
                    $elementType,
                    $item->getId()
                );
            } catch (NotFoundException $exception) {
                // ElasticSearch/DB sync lag: the index references an element that no longer
                // exists in the database.
                $this->pimcoreLogger->warning(
                    'Skipping grid element missing from the database (index out of sync)',
                    [
                        'elementType' => $elementType,
                        'elementId' => $item->getId(),
                        'exception' => $exception,
                    ]
                );
            }
        }

        return new Collection(
            totalItems: $searchResultItem->getTotalItems(),
            items: $data
        );
    }

    private function findColumnConfiguration(string $key, array $columnConfigurations): ?ColumnConfiguration
    {
        $configs = array_filter(
            $columnConfigurations,
            static fn (ColumnConfiguration $columnConfiguration) => $columnConfiguration->getKey() === $key
        );

        return reset($configs) ?: null;
    }
}
