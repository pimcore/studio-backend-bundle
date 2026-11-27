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
use Pimcore\Bundle\GenericDataIndexBundle\Model\SearchIndexAdapter\MappingProperty;
use Pimcore\Bundle\StaticResolverBundle\Lib\Cache\RuntimeCacheResolverInterface;
use Pimcore\Bundle\StaticResolverBundle\Lib\ToolResolverInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\ClassDefinitionResolverInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\DataObjectServiceResolverInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\LocalizedFieldResolverInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\Element\ServiceResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\ColumnReferenceServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\ColumnVariantExpanderInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\KeyLocalizationResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Grid\GridSearchInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\SearchResult\SearchResultItemInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Schema\DataObject as StudioDataObject;
use Pimcore\Bundle\StudioBackendBundle\Element\Schema\Permissions;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Util\Config;
use Pimcore\Bundle\StudioBackendBundle\Filter\MappedParameter\FilterParameter;
use Pimcore\Bundle\StudioBackendBundle\Grid\Column\ColumnCollectorInterface;
use Pimcore\Bundle\StudioBackendBundle\Grid\Column\ColumnDefinitionInterface;
use Pimcore\Bundle\StudioBackendBundle\Grid\Column\ColumnResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Grid\Column\ColumnType;
use Pimcore\Bundle\StudioBackendBundle\Grid\Column\CoreElementColumnResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Grid\Column\DeniedColumnDataResolverInterface;
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
use Pimcore\Bundle\StudioBackendBundle\Response\WorkflowPermissionsAwareInterface;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementPermissions;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementTypes;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\ElementProviderTrait;
use Pimcore\Localization\LocaleServiceInterface;
use Pimcore\Model\DataObject\ClassDefinition;
use Pimcore\Model\DataObject\ClassDefinition\Data\Classificationstore;
use Pimcore\Model\DataObject\ClassDefinition\Data\Localizedfields;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\Element\ElementInterface;
use Pimcore\Model\User;
use Pimcore\Model\UserInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use TypeError;
use function array_key_exists;
use function in_array;
use function is_array;
use function is_string;

/**
 * @internal
 */
final class GridService implements GridServiceInterface
{
    use ElementProviderTrait;

    /**
     * The column types of the system columns of a data object. The search result entry of a row carries the
     * value of each of them, so a row of these columns does not need the data object.
     */
    private const ENTRY_SYSTEM_COLUMN_TYPES = [
        ColumnType::SYSTEM_ID->value,
        ColumnType::SYSTEM_STRING->value,
        ColumnType::SYSTEM_DATETIME->value,
        ColumnType::SYSTEM_BOOLEAN->value,
        ColumnType::SYSTEM_INTEGER->value,
        ColumnType::SYSTEM_USER->value,
    ];

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

    /**
     * @var string[]|null
     */
    private ?array $dataObjectSystemColumnKeys = null;

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
        private readonly DataObjectServiceResolverInterface $dataObjectServiceResolver,
        private readonly ToolResolverInterface $toolResolver,
        private readonly LocaleServiceInterface $localeService,
        private readonly RuntimeCacheResolverInterface $runtimeCacheResolver,
        private readonly ColumnReferenceServiceInterface $columnReferenceService,
        private readonly ColumnVariantExpanderInterface $columnVariantExpander,
        private readonly KeyLocalizationResolverInterface $keyLocalizationResolver,
        private readonly SystemColumnServiceInterface $systemColumnService,
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function isLocaleViewableForElement(
        ElementInterface $element,
        ?string $locale,
        ?UserInterface $user = null,
        bool $isLocalizedField = false,
    ): bool {
        if (!$element instanceof Concrete) {
            return true;
        }

        if ($locale === null) {
            if (!$isLocalizedField) {
                return true;
            }

            // A localized field read without an explicit locale resolves to the current request
            // locale or the default language (Localizedfield::getLanguage()). That effective
            // locale must be authorized like an explicit one - otherwise omitting the locale
            // from the column configuration would bypass the language permission entirely.
            $locale = $this->getImplicitReadLocale();

            if ($locale === null) {
                return true;
            }
        }

        $user ??= $this->securityService->getCurrentUser();

        /** @var User $user */
        if ($user->isAdmin()) {
            return true;
        }

        $allowedView = $this->dataObjectServiceResolver->getLanguagePermissions($element, $user, 'lView');

        if ($allowedView === null) {
            return true;
        }

        if (!array_key_exists($locale, $allowedView)) {
            return false;
        }

        // the language independent bucket has no fallback language
        if ($locale === MappingProperty::NOT_LOCALIZED_KEY) {
            return true;
        }

        return !$this->isFallbackLanguageDenied($locale, $allowedView);
    }

    /**
     * An empty value for an allowed locale can silently fall back to the default language
     * (LocalizedValueTrait::getLocalizedValue()). If that fallback language isn't permitted,
     * the column must be treated as not viewable - otherwise a denied language's value could
     * still leak out through the fallback.
     *
     * @param array<string, mixed> $allowedView
     */
    private function isFallbackLanguageDenied(string $locale, array $allowedView): bool
    {
        if (!$this->localizedFieldResolver->doGetFallbackValues()) {
            return false;
        }

        $defaultLanguage = $this->toolResolver->getDefaultLanguage();

        return $defaultLanguage !== null
            && $defaultLanguage !== $locale
            && !array_key_exists($defaultLanguage, $allowedView);
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
            // The classification store column filters resolve their identifiers in the store of the field of
            // this class, so the filter has to carry the id as well as the name.
            $filter->setClassId($classId);
        }

        $result = $this->gridSearch->searchDataObjects($gridParameter);

        $fallbackBackup = $this->localizedFieldResolver->getGetFallbackValues();
        $this->localizedFieldResolver->setGetFallbackValues($gridParameter->getApplyFallbackLanguages());

        try {
            return $this->getCollectionFromSearchResult(
                $result,
                $gridParameter,
                ElementTypes::TYPE_OBJECT,
                $classId
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
        ?ElementInterface $coreElement = null,
    ): array {
        $data = [];

        $databaseElement = $coreElement;
        if ($databaseElement === null
            && $this->requiresDatabaseElement($columnCollection, $element, $elementType, $isExport, $user)
        ) {
            $databaseElement = $this->getElement($this->serviceResolver, $elementType, $elementId);
        }

        if ($user !== null && $databaseElement !== null) {
            $this->securityService->hasElementPermission(
                $databaseElement,
                $user,
                ElementPermissions::VIEW_PERMISSION
            );
        }

        if ($isExport && $databaseElement->getType() === ElementTypes::TYPE_FOLDER) {
            throw new InvalidArgumentException(
                message: 'Exporting folder elements is not supported',
                errorKey: Config::ELEMENT_FOLDER_COLLECTION_NOT_SUPPORTED->value
            );
        }

        $classId = $databaseElement instanceof Concrete ? $databaseElement->getClassId() : null;
        if ($classId !== null) {
            $this->columnReferenceService->prime($classId, $columnCollection);
        }

        $permissions = $this->resolvePermissions($element, $databaseElement, $elementType, $elementId, $coreElement);

        foreach ($columnCollection->getColumns() as $column) {
            // move this to the resolver
            if (!$this->supports($column, $elementType)) {
                continue;
            }

            $columnData = $this->resolveColumnDataRespectingLocalePermission(
                $column,
                $databaseElement,
                $element,
                $isExport,
                $user
            );

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
     * Whether the row has to be built from the database element. A data object row whose columns are all
     * system columns is built from its search result entry alone, which saves loading the object. Everything
     * that has to see the object keeps loading it: exports, rows that are checked for a user (the view
     * permission and its events), rows without an entry and folders.
     */
    private function requiresDatabaseElement(
        ColumnCollection $columnCollection,
        ?StudioElementInterface $element,
        string $elementType,
        bool $isExport,
        ?UserInterface $user,
    ): bool {
        if ($isExport) {
            return true;
        }

        if ($elementType !== ElementTypes::TYPE_OBJECT) {
            return false;
        }

        if ($element === null || $user !== null) {
            return true;
        }

        // A folder is not described by its entry the way an object is (it has no class).
        if ($element instanceof StudioDataObject && $element->getType() === ElementTypes::TYPE_FOLDER) {
            return true;
        }

        return !$this->isEverySupportedColumnAnEntrySystemColumn($columnCollection, $elementType);
    }

    private function isEverySupportedColumnAnEntrySystemColumn(
        ColumnCollection $columnCollection,
        string $elementType
    ): bool {
        $systemColumnKeys = $this->getDataObjectSystemColumnKeys();

        foreach ($columnCollection->getColumns() as $column) {
            if (!$this->supports($column, $elementType)) {
                continue;
            }

            // A column with a locale is checked against the language permissions of the object.
            if ($column->getLocale() !== null
                || !in_array($column->getType(), self::ENTRY_SYSTEM_COLUMN_TYPES, true)
                || !in_array($column->getKey(), $systemColumnKeys, true)
                || !$this->getColumnResolvers()[$column->getType()] instanceof StudioElementColumnResolverInterface
            ) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return string[]
     */
    private function getDataObjectSystemColumnKeys(): array
    {
        if ($this->dataObjectSystemColumnKeys === null) {
            $systemColumns = $this->systemColumnService->getSystemColumnsForDataObjects();
            $this->dataObjectSystemColumnKeys = array_keys($systemColumns);
        }

        return $this->dataObjectSystemColumnKeys;
    }

    /**
     * {@inheritdoc}
     */
    public function getGridForProvidedElements(
        GridParameter $gridParameter,
        array $elements,
        int $totalItems,
        string $elementType,
    ): Collection {
        $user = $this->securityService->getCurrentUser();
        // the class the provided objects are of, so a column referenced by identifier expands as in the listing
        $classId = null;
        foreach ($elements as $providedElement) {
            if ($providedElement->getElement() instanceof Concrete) {
                $classId = $providedElement->getElement()->getClassId();

                break;
            }
        }

        $columns = $this->getConfigurationFromArray($gridParameter->getColumns(), $classId);

        $fallbackBackup = $this->localizedFieldResolver->getGetFallbackValues();
        $this->localizedFieldResolver->setGetFallbackValues($gridParameter->getApplyFallbackLanguages());

        try {
            $data = [];
            foreach ($elements as $providedElement) {
                $entry = $providedElement->getEntry();

                try {
                    $data[] = $this->getGridDataForElement(
                        $columns,
                        $entry,
                        $elementType,
                        $entry->getId(),
                        false,
                        $user,
                        $providedElement->getElement()
                    );
                } catch (ForbiddenException) {
                    // Not viewable: the search index would not have listed it either.
                }
            }

            return new Collection(totalItems: $totalItems, items: $data);
        } finally {
            $this->localizedFieldResolver->setGetFallbackValues($fallbackBackup);
        }
    }

    private function resolveColumnDataRespectingLocalePermission(
        Column $column,
        ?ElementInterface $databaseElement,
        ?StudioElementInterface $element,
        bool $isExport,
        ?UserInterface $user,
    ): ColumnData {
        $gateLocale = $column->getLocale();
        $isLocalizedField = $gateLocale === null
            && $databaseElement !== null
            && $this->isLocalizedClassField($databaseElement, $column->getKey());

        $storeGate = null;
        if ($databaseElement !== null) {
            $storeGate = $this->resolveClassificationStoreGate($databaseElement, $column);
        }

        if ($storeGate !== null) {
            // the language the read resolves to decides the permission, not the column's raw locale
            [$gateLocale, $isLocalizedField] = $storeGate;
        }

        if ($databaseElement !== null
            && !$this->isLocaleViewableForElement($databaseElement, $gateLocale, $user, $isLocalizedField)
        ) {
            return $this->resolveDeniedColumnData($column, $databaseElement, $storeGate !== null);
        }

        $resolver = $this->getColumnResolvers()[$column->getType()];

        return match (true) {
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
    }

    private function resolveDeniedColumnData(
        Column $column,
        ElementInterface $databaseElement,
        bool $isStoreColumn,
    ): ColumnData {
        $deniedResolver = $isStoreColumn ? ($this->getColumnResolvers()[$column->getType()] ?? null) : null;
        if ($deniedResolver instanceof DeniedColumnDataResolverInterface) {
            $denied = $deniedResolver->resolveDeniedForCoreElement($column, $databaseElement);
            if ($denied !== null) {
                return $denied;
            }
        }

        $variant = $column->getConfig()['variant'] ?? null;

        return new ColumnData(
            key: $column->getKey() . (is_string($variant) && $variant !== '' ? '#' . $variant : ''),
            locale: $column->getLocale(),
            value: null,
            fieldType: $column->getType(),
        );
    }

    /**
     * The locale core actually reads when a localized getter is called without an explicit
     * locale - the current request locale if it is a configured language, otherwise the
     * default language. Mirrors Localizedfield::getLanguage().
     */
    public function getImplicitReadLocale(): ?string
    {
        $currentLocale = $this->localeService->getLocale();
        if ($currentLocale !== null && $this->toolResolver->isValidLanguage($currentLocale)) {
            return $currentLocale;
        }

        return $this->toolResolver->getDefaultLanguage();
    }

    /**
     * The language gate of a Classification Store column, as [locale, localized]: the key decides its own
     * language, so a key that is not localized is read from the language independent bucket and needs no
     * language permission whatever locale the column carries, while a localized one is gated on the
     * column's locale or, without one, the implicit read locale. A column naming no key falls back to
     * the field: localized when any key of its store is. Null when the column is no Classification Store
     * column of the element's class.
     *
     * @return array{0: ?string, 1: bool}|null
     */
    private function resolveClassificationStoreGate(ElementInterface $element, Column $column): ?array
    {
        if (!$element instanceof Concrete || $column->getType() !== 'dataobject.classificationstore') {
            return null;
        }

        $definition = $element->getClass()->getFieldDefinition($column->getKey());
        if (!$definition instanceof Classificationstore) {
            return null;
        }

        $keyId = $this->resolveColumnKeyId($element, $column);
        // asked every time: the key's localized flag is configuration that can change while a worker lives
        $localized = $keyId !== null
            ? $this->keyLocalizationResolver->isKeyLocalized($keyId, $definition)
            : $this->keyLocalizationResolver->hasLocalizedKey($definition);

        if (!$localized) {
            // read from the language independent bucket, which is itself a permission: the export
            // resolver does not intersect with the permitted languages the way the adapter does
            return [MappingProperty::NOT_LOCALIZED_KEY, false];
        }

        // an empty locale is no locale, as the resolver reads it
        $locale = $column->getLocale() === '' ? null : $column->getLocale();

        return [$locale, $locale === null];
    }

    private function resolveColumnKeyId(Concrete $element, Column $column): ?int
    {
        try {
            $reference = $this->columnReferenceService->resolve(
                $element->getClassId(),
                $column->getKey(),
                $column->getConfig()
            );
        } catch (Exception) {
            $reference = null;
        }

        if ($reference !== null) {
            return $reference->getKeyId();
        }

        $keyId = $column->getConfig()['keyId'] ?? null;

        return is_numeric($keyId) ? (int)$keyId : null;
    }

    private function isLocalizedClassField(ElementInterface $element, string $key): bool
    {
        if (!$element instanceof Concrete) {
            return false;
        }

        $localizedFields = $element->getClass()->getFieldDefinition('localizedfields');

        return $localizedFields instanceof Localizedfields
            && $localizedFields->getFieldDefinition($key) !== null;
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
        ?ElementInterface $coreElement = null,
    ): ?Permissions {
        if ($element === null) {
            return null;
        }

        $permissions = $element->getPermissions();

        // A caller-built entry carries no reliable workflow flag, so the provided element decides.
        if ($coreElement !== null) {
            return $this->workflowPermissionMerger->mergeWorkflowPermissions($permissions, $coreElement);
        }

        if (!$element instanceof WorkflowPermissionsAwareInterface || !$element->getHasWorkflowWithPermissions()) {
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
        array $columnsDefinitions,
        ?string $classId = null
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

        return $this->getConfigurationFromArray($exportableColumns, $classId);
    }

    public function getColumnKeys(ColumnCollection $columnCollection, bool $withGroup = false): array
    {
        return array_map(
            function (Column $column) use ($withGroup): string {
                if ($withGroup === true && !$column->getGroup()) {
                    throw new InvalidArgumentException('Group must be set when withGroup is true');
                }

                $key = $this->appendLocale($this->getColumnKeyName($column, $withGroup), $column);

                return ($withGroup ? ($column->getGroup()[0] ?? '') . '~' : '') . $key;
            },
            $columnCollection->getColumns()
        );
    }

    /**
     * The column's name as an export header carries it, a variant column addressing its variant as `#variant`.
     */
    private function getColumnKeyName(Column $column, bool $withGroup): string
    {
        $fd = $column->getConfig()['fieldDefinition'] ?? null;
        $field = $withGroup ? 'name' : 'title';
        $key = $fd ? ($fd[$field] ?? $column->getKey()) : $column->getKey();

        if ($column->getType() === 'dataobject.classificationstore' && isset($column->getConfig()['variant'])) {
            $key .= '#' . $column->getConfig()['variant'];
        }

        return $key;
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
        array $config,
        ?string $classId = null
    ): ColumnCollection {

        $columns = [];
        foreach ($config as $column) {
            try {
                $columns[] = new Column(
                    key: $column['key'],
                    locale: $column['locale'] ?? null,
                    type: $column['type'],
                    group: $column['group'] ?? null,
                    config: is_array($column['config'] ?? null) ? $column['config'] : [],
                    width: $column['width'] ?? null
                );
            } catch (Exception|TypeError $e) {
                // Column's strictly typed constructor raises a TypeError for malformed request data
                throw new InvalidArgumentException('Invalid column configuration', $e);
            }
        }

        return new ColumnCollection($this->columnVariantExpander->expand($columns, $classId));
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
        string $elementType,
        ?string $classId = null
    ): Collection {
        $items = $searchResultItem->getItems();

        if (empty($items)) {
            return new Collection(totalItems: 0, items: []);
        }

        $data = [];
        $columns = $this->getConfigurationFromArray($gridParameter->getColumns(), $classId);
        foreach ($items as $item) {
            $rowCacheKey = $this->getRowObjectCacheKey($elementType, $item->getId());

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
            } finally {
                $this->releaseRowObject($rowCacheKey);
            }
        }

        return new Collection(
            totalItems: $searchResultItem->getTotalItems(),
            items: $data
        );
    }

    /**
     * Returns the runtime cache key of the data object a grid row is about to load, or null if the row
     * does not load one or the object is already in the runtime cache (other code may still hold it).
     */
    private function getRowObjectCacheKey(string $elementType, int $elementId): ?string
    {
        if ($elementType !== ElementTypes::TYPE_OBJECT) {
            return null;
        }

        // Same key as Element\Service::getElementCacheTag(), under which AbstractObject::getById() keeps the object.
        $cacheKey = $elementType . '_' . $elementId;

        return $this->runtimeCacheResolver->isRegistered($cacheKey) ? null : $cacheKey;
    }

    /**
     * Drops the row's own data object from the runtime cache once its row is built, so the memory
     * of a grid request does not grow with the page size.
     */
    private function releaseRowObject(?string $cacheKey): void
    {
        if ($cacheKey === null || !$this->runtimeCacheResolver->isRegistered($cacheKey)) {
            return;
        }

        $this->runtimeCacheResolver->getInstance()->offsetUnset($cacheKey);
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
