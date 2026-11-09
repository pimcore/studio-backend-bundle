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

namespace Pimcore\Bundle\StudioBackendBundle\DataObject\Data\Adapter;

use Exception;
use Pimcore\Bundle\GenericDataIndexBundle\Model\SearchIndexAdapter\MappingProperty;
use Pimcore\Bundle\StaticResolverBundle\Lib\ToolResolverInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\ClassificationStore\DefinitionCacheResolverInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\ClassificationStore\GroupConfigResolverInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\ClassificationStore\ServiceResolverInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\DataObjectServiceResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\KeyQualifierResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\KeyRelationResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\QualifiedValue;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\QualifiedValueSerializerInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\StoreEngineResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\DataInheritanceInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\DataNormalizerInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\DetailDataInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\Model\FieldContextData;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\SearchPreviewDataInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\SetterDataInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Service\DataAdapterLoaderInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Service\DataAdapterServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Service\DataServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Service\InheritanceServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Util\Trait\ValidateObjectDataTrait;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\DatabaseException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\EnvironmentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\LanguageServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementPermissions;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\ClassDefinition\Data\Classificationstore as ClassificationstoreDefinition;
use Pimcore\Model\DataObject\ClassDefinition\Data\InputQuantityValue;
use Pimcore\Model\DataObject\ClassDefinition\Data\QuantityValue as QuantityValueDefinition;
use Pimcore\Model\DataObject\Classificationstore;
use Pimcore\Model\DataObject\Classificationstore as ClassificationstoreModel;
use Pimcore\Model\DataObject\Classificationstore\GroupConfig;
use Pimcore\Model\DataObject\Classificationstore\KeyConfig;
use Pimcore\Model\DataObject\Classificationstore\KeyGroupRelation;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\DataObject\Data\CalculatedValue;
use Pimcore\Model\DataObject\Data\QuantityValue;
use Pimcore\Model\UserInterface;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use function array_key_exists;
use function array_keys;
use function array_merge;
use function count;
use function in_array;
use function is_array;
use function is_int;

/**
 * @internal
 */
#[AutoconfigureTag(DataAdapterLoaderInterface::ADAPTER_TAG)]
final readonly class ClassificationStoreAdapter implements
    SetterDataInterface,
    DataNormalizerInterface,
    DetailDataInterface,
    DataInheritanceInterface,
    SearchPreviewDataInterface
{
    use ValidateObjectDataTrait;

    public function __construct(
        private DataObjectServiceResolverInterface $dataObjectServiceResolver,
        private DefinitionCacheResolverInterface $definitionCacheResolver,
        private DataAdapterServiceInterface $dataAdapterService,
        private DataServiceInterface $dataService,
        private GroupConfigResolverInterface $groupConfigResolver,
        private InheritanceServiceInterface $inheritanceService,
        private KeyQualifierResolverInterface $keyQualifierResolver,
        private KeyRelationResolverInterface $keyRelationResolver,
        private LanguageServiceInterface $languageService,
        private QualifiedValueSerializerInterface $qualifiedValueSerializer,
        private ServiceResolverInterface $serviceResolver,
        private SecurityServiceInterface $securityService,
        private StoreEngineResolverInterface $storeEngineResolver,
        private ToolResolverInterface $toolResolver,
    ) {
    }

    /**
     * @throws Exception
     */
    public function getDataForSetter(
        Concrete $element,
        Data $fieldDefinition,
        string $key,
        array $data,
        UserInterface $user,
        ?FieldContextData $contextData = null,
        bool $isPatch = false
    ): ?Classificationstore {

        if (!$fieldDefinition instanceof ClassificationstoreDefinition) {
            return null;
        }

        $store = $data[$key];

        if ($isPatch) {
            $elementStore = $this->handleNormalize($element->get($key), $fieldDefinition, $user);
            $store['activeGroups'] = $this->getActiveGroupsFromStore($store);
            $store = $this->patchStore($fieldDefinition, $elementStore, $store);
        }

        $activeGroups = $store['activeGroups'] ?? [];
        $deletedGroupIds = $this->getDeletedGroupIds($store);
        $activeGroups = array_diff_key($activeGroups, array_flip($deletedGroupIds));

        if (empty($activeGroups)) {
            $container = $this->getContainer($element, $key, $contextData);
            $container->setActiveGroups([]);
            $container->setGroupCollectionMappings([]);
            $this->cleanupStoreGroups($container);

            return $container;
        }

        $groupCollectionMapping = $store['groupCollectionMapping'] ?? [];
        $groupCollectionMapping = array_diff_key($groupCollectionMapping, array_flip($deletedGroupIds));
        $container = $this->getContainer($element, $key, $contextData);
        $store = array_diff_key($store, array_flip($deletedGroupIds));
        unset($store['activeGroups'], $store['groupCollectionMapping']);
        $this->setStoreValues($element, $user, $fieldDefinition, $container, $store, $activeGroups, $isPatch);
        if (!empty($groupCollectionMapping)) {
            $this->setMapping($container, $container->getActiveGroups(), $groupCollectionMapping);
        }
        $this->cleanupStoreGroups($container);

        return $container;
    }

    public function normalize(
        mixed $value,
        Data $fieldDefinition
    ): ?array {
        return $this->handleNormalize($value, $fieldDefinition, useComputedValues: true);
    }

    public function getDetailData(
        Concrete $object,
        mixed $value,
        Data $fieldDefinition,
        ?FieldContextData $contextData = null,
    ): ?array {
        return $this->handleNormalize($value, $fieldDefinition);
    }

    public function getFieldInheritance(
        Concrete $object,
        Data $fieldDefinition,
        string $key,
        ?FieldContextData $contextData = null
    ): array {
        $inheritedData = [];
        if (!$fieldDefinition instanceof ClassificationstoreDefinition) {
            return [];
        }
        $languages = $this->getValidLanguages($object, $fieldDefinition->isLocalized());
        $collection = $this->getStoreDefinitions($object, $fieldDefinition);
        if (empty($collection)) {
            return [
                $this->inheritanceService->getFieldInheritanceData($object, $fieldDefinition, $key, $contextData),
            ];
        }

        $container = $this->getContainer($object, $key, $contextData);
        $resolveInheritedValue = $contextData?->shouldResolveInheritedValue() ?? false;
        foreach ($collection as $groupId => $groupDefinitions) {
            foreach ($groupDefinitions as $groupKeyId => $definition) {
                $keyConfig = $fieldDefinition->getKeyConfiguration($groupKeyId);
                // an ancestor's qualified slot holds the entry list, which is no value of the key's
                // own type: where the origin is still reported, the value behind it is not resolved
                $resolveKeyValue = $resolveInheritedValue
                    && !$this->keyQualifierResolver->declaresQualifiers($keyConfig);

                foreach ($languages as $language) {
                    $fieldContextData = new FieldContextData(
                        $container,
                        $language,
                        $groupId,
                        $groupKeyId,
                        resolveInheritedValue: $resolveKeyValue,
                        // the sibling arm of that guard, which does resolve the value: an ancestor's
                        // slot is read under the key's declaration, so a key declaring nothing reads
                        // the one value it always did even where the row still holds a list it hides
                        qualifiedValueSerializer: $this->qualifiedValueSerializer,
                        classificationStoreKeyConfig: $keyConfig
                    );
                    $inheritedData[$groupId][$language][$groupKeyId] = $this->inheritanceService
                        ->getFieldInheritanceData($object, $definition, $key, $fieldContextData);
                }
            }
        }

        return $inheritedData;
    }

    public function getPreviewFieldData(
        mixed $value,
        Data $fieldDefinition,
        array $data
    ): array {
        if (!$value instanceof ClassificationstoreModel ||
            !$fieldDefinition instanceof ClassificationstoreDefinition
        ) {
            return $data;
        }

        $fieldName =$this->dataService->getPreviewFieldName($fieldDefinition);
        $validLanguages = $this->getValidLanguages($value->getObject(), $fieldDefinition->isLocalized());
        $activeGroups = $this->getActiveGroupsConfig($value->getActiveGroups());
        $keysByGroup = $this->keyRelationResolver->getByGroups(array_keys($activeGroups));
        foreach ($activeGroups as $groupId => $groupConfig) {
            $keys = $keysByGroup[$groupId];
            $groupName = $groupConfig->getName();
            foreach ($keys as $key) {
                foreach ($validLanguages as $validLanguage) {
                    $previewValue = $this->getPreviewValue($value, $groupId, $key, $validLanguage, $data);
                    $previewKey = $groupName . ' - ' . $key->getName() .
                        ($fieldDefinition->isLocalized() ? ' / ' . $validLanguage : null);
                    $data[$fieldName][$previewKey] = $previewValue;
                }
            }
        }

        return $data;
    }

    /**
     * The stored store with the supplied one written over it.
     *
     * `array_replace_recursive()` merges two arrays element by element, which is what the group,
     * language and key buckets need and the wrong thing for the list a qualified key's slot holds:
     * a write naming one entry would land on the first element of the stored list and keep the rest
     * of it by position. A qualified slot therefore carries the write's own entries through
     * unmerged, and they are merged by qualifier in {@see self::getQualifiedSetterData()}, which
     * reads the row the container holds rather than `$elementStore` - the narrower view of that row
     * this method merges into, built under the user's language *view* permissions and only over the
     * groups currently active. A slot missing from that view is one this code cannot see rather
     * than an empty one, and merging a list against it would drop every entry the write leaves
     * alone.
     *
     * Which qualified slots the patch carries at all is {@see self::patchQualifiedSlots()}.
     */
    private function patchStore(
        ClassificationstoreDefinition $definition,
        ?array $elementStore,
        array $store
    ): array {
        $patched = array_replace_recursive($elementStore, $store);

        foreach ($patched as $groupId => $groupData) {
            // `activeGroups` and `groupCollectionMapping` are the two buckets that are not a group
            if (!is_int($groupId) || !is_array($groupData)) {
                continue;
            }

            foreach ($groupData as $language => $keys) {
                if (!is_array($keys)) {
                    continue;
                }

                $patched[$groupId][$language] = $this->patchQualifiedSlots(
                    $definition,
                    $keys,
                    $this->getSuppliedKeys($store, $groupId, $language)
                );
            }
        }

        return $patched;
    }

    /**
     * The qualified slots of one (group, language) bucket, held to what the write actually named.
     *
     * A slot the write names carries the write's own entries, unmerged. A slot it does not name is
     * left out of the patch altogether rather than carried in from `$elementStore`: there is no
     * write to make for it, the row already holds it, and an entry the request never supplied would
     * otherwise be validated against the key's declaration as it reads now - so a patch of one key
     * would fail because another key of the same bucket holds a variant an administrator has since
     * removed from it, or a capability its store's engine does not report.
     *
     * Which slots those are is decided by the key's declaration alone, as it is in
     * {@see self::processGroupKeys()}: a key declaring nothing serializes one value whatever its
     * row still holds, so its slot is merged like any other single value.
     *
     * @param array<array-key, mixed> $keys
     * @param array<array-key, mixed> $supplied
     *
     * @return array<array-key, mixed>
     */
    private function patchQualifiedSlots(
        ClassificationstoreDefinition $definition,
        array $keys,
        array $supplied
    ): array {
        foreach ($keys as $keyId => $value) {
            $keyConfig = $definition->getKeyConfiguration((int) $keyId);

            if (!$this->keyQualifierResolver->declaresQualifiers($keyConfig)) {
                continue;
            }

            if (array_key_exists($keyId, $supplied)) {
                $keys[$keyId] = $supplied[$keyId];

                continue;
            }

            unset($keys[$keyId]);
        }

        return $keys;
    }

    /**
     * The keys of one (group, language) bucket the write itself supplied, as opposed to the ones
     * {@see self::patchStore()} merged in from the stored store.
     *
     * @return array<array-key, mixed>
     */
    private function getSuppliedKeys(array $store, int $groupId, int|string $language): array
    {
        $keys = $store[$groupId][$language] ?? null;

        return is_array($keys) ? $keys : [];
    }

    private function handleNormalize(
        mixed $value,
        Data $fieldDefinition,
        ?UserInterface $user = null,
        bool $useComputedValues = false,
    ): ?array {
        if (!$value instanceof ClassificationstoreModel ||
            !$fieldDefinition instanceof ClassificationstoreDefinition
        ) {
            return null;
        }

        $validLanguages = $this->getValidLanguages(
            $value->getObject(),
            $fieldDefinition->isLocalized(),
            ElementPermissions::LANGUAGE_VIEW_PERMISSIONS,
            $user
        );
        $resultItems = [];

        $resultItems['activeGroups'] = $value->getActiveGroups();
        $resultItems['groupCollectionMapping'] = $value->getGroupCollectionMappings();

        $activeGroups = $this->getActiveGroupsConfig($resultItems['activeGroups']);
        $keysByGroup = $this->keyRelationResolver->getByGroups(array_keys($activeGroups));
        foreach ($activeGroups as $groupId => $groupConfig) {
            $resultItems[$groupId] = [];
            $keys = $keysByGroup[$groupId];
            foreach ($validLanguages as $validLanguage) {
                foreach ($keys as $key) {
                    $normalizedValue = $this->getResolvedValue(
                        $value,
                        $groupId,
                        $key,
                        $validLanguage,
                        $useComputedValues,
                    );

                    if ($normalizedValue !== null) {
                        $resultItems[$groupId][$validLanguage][$key->getKeyId()] = $normalizedValue;
                    }
                }
            }
        }

        return $resultItems;
    }

    /**
     * @throws NotFoundException
     */
    private function getContainer(
        Concrete $element,
        string $key,
        ?FieldContextData $contextData
    ): Classificationstore {
        $container = $this->getValidFieldValue($element, $key, $contextData);

        if (!$container instanceof Classificationstore) {
            return new Classificationstore();
        }

        return $container;
    }

    private function setMapping(
        Classificationstore $container,
        array $activeGroups,
        array $groupCollectionMapping
    ): void {
        $correctedMapping = array_filter($groupCollectionMapping, static function ($groupId) use ($activeGroups) {
            return isset($activeGroups[$groupId]) && $activeGroups[$groupId];
        }, ARRAY_FILTER_USE_KEY);

        $container->setGroupCollectionMappings($correctedMapping);
    }

    /**
     * @throws Exception
     */
    private function setStoreValues(
        Concrete $element,
        UserInterface $user,
        ClassificationstoreDefinition $definition,
        Classificationstore $container,
        array $store,
        array $activeGroups,
        bool $isPatch
    ): void {

        $editableLanguages = $this->getValidLanguages(
            $element,
            $definition->isLocalized(),
            ElementPermissions::LANGUAGE_EDIT_PERMISSIONS,
            $user
        );

        foreach ($store as $groupId => $groupData) {
            foreach ($groupData as $language => $keys) {
                if (!in_array($language, $editableLanguages, true)) {
                    continue;
                }
                $this->processGroupKeys($element, $user, $definition, $container, $language, $groupId, $keys, $isPatch);
                $activeGroups[$groupId] = true;
            }
        }

        $container->setActiveGroups($activeGroups);
    }

    /**
     * @throws Exception
     */
    private function processGroupKeys(
        Concrete $element,
        UserInterface $user,
        ClassificationstoreDefinition $definition,
        Classificationstore $container,
        string $language,
        int $groupId,
        array $keys,
        bool $isPatch
    ): void {
        foreach ($keys as $keyId => $value) {
            $keyConfig = $definition->getKeyConfiguration($keyId);
            $fieldDefinition = $this->serviceResolver->getFieldDefinitionFromKeyConfig($keyConfig);

            if ($fieldDefinition === null) {
                continue;
            }

            $adapter = $this->dataAdapterService->tryDataAdapter($fieldDefinition->getFieldType());
            if ($adapter === null) {
                continue;
            }

            // the current value the key's own type adapter sees is the one entry's, not the slot's
            $convert = fn (
                mixed $raw,
                ?QualifiedValue $current = null,
                bool $patch = false
            ): mixed => $adapter->getDataForSetter(
                $element,
                $fieldDefinition,
                $fieldDefinition->getName(),
                [$fieldDefinition->getName() => $raw],
                $user,
                new FieldContextData(
                    $container,
                    $language,
                    $groupId,
                    $keyId,
                    classificationStoreValue: $current
                ),
                $patch
            );

            $storedSlot = $this->readStoredSlot($container, $groupId, $keyId, $language);

            if ($this->keyQualifierResolver->declaresQualifiers($keyConfig)) {
                $setterData = $this->getQualifiedSetterData(
                    $value,
                    $keyConfig,
                    $fieldDefinition,
                    $definition->getStoreId(),
                    $storedSlot,
                    $isPatch,
                    $convert
                );
            } else {
                // the entries of a qualified slot are each validated in getQualifiedSetterData(), which
                // refuses the write rather than handing back a list this guard would read as one value
                $converted = $convert(
                    $value,
                    $this->getCurrentOfSingleValue($storedSlot, $keyConfig),
                    $isPatch
                );

                if (!$this->validateEncryptedField($fieldDefinition, $converted)) {
                    continue;
                }

                $setterData = $this->assembleSlot(
                    [new QualifiedValue($converted)],
                    $this->qualifiedValueSerializer->getHiddenEntries($storedSlot, $keyConfig)
                );
            }

            $container->setLocalizedKeyValue($groupId, $keyId, $setterData, $language);
        }
    }

    /**
     * What the slot of a key that declares qualifiers stores: the entries the write supplies, each
     * value taken through the adapter of the key's own type, as a list carrying their qualifiers.
     *
     * A patch merges them over the entries the declaration permits, by qualifier and never by
     * position in the list, so an entry the write does not name survives as itself. The engine is
     * asked about the supplied entries alone rather than about the merged list: the stored ones
     * were accepted when they were written, and holding this write to them would make a key
     * uneditable on an engine that no longer reports what it once did.
     *
     * Only the supplied entries go through the key's own type adapter, and the merge is onto the
     * *raw* entries the row holds. A stored entry carried over by the merge was never supplied by
     * this request, so there is nothing of it to convert: it is already in the shape the row stores
     * and taking it through the setter a second time hands the adapter its own normalized output -
     * `ManyToManyRelationAdapter::normalize()` answers with `RelatedElementData` objects and its
     * setter reads arrays, so an untouched relation sibling would take the whole patch down. The
     * same raw reading is what the current value handed to the adapter is taken from, which is how
     * every other context reads this slot
     * ({@see \Pimcore\Bundle\StudioBackendBundle\DataObject\Data\Model\FieldContextData}) and what
     * an adapter resolving a patch against the current value expects, since
     * {@see \Pimcore\Bundle\StudioBackendBundle\DataObject\Service\Data\RelationDataService::getSetterData()}
     * normalizes it itself.
     *
     * An entry the row holds and the declaration no longer permits is hidden, not deleted: it is
     * carried over untouched, so a write that does not mention it - a PUT of what a read published
     * included - leaves it in the row.
     *
     * A write that resolves to the single default slot - one entry, no variant, position 0 - and
     * leaves nothing hidden stores the value itself instead, which is the slot core's single-value
     * path addresses and therefore exactly what the key stored before it declared anything.
     *
     * The engine is asked here rather than once for the whole field, so that a field holding no
     * qualified slot at all - every field until one of its keys declares - resolves no engine.
     *
     * Each entry's own value is held to the key's type before it is wrapped, because the list the
     * slot holds is not a value the type's own guard can read: an encrypted key handed the wrapped
     * list would fail that guard for the whole slot. A value failing it refuses the write here,
     * since a slot dropped under a successful response is data lost without an answer.
     *
     * No entry reaches the key's own type adapter as a patch of anything. The patch of a qualified
     * slot is resolved above, by qualifier, so what each entry then carries is one whole value of
     * the key's type and not the partial envelope an adapter in patch mode reads - and the entries
     * the merge carries over from the row were never supplied by this request at all. Its sibling
     * arm, the slot that is not qualified, keeps handing the write's patch flag through unchanged.
     *
     * @param callable(mixed, ?QualifiedValue): mixed $convert
     *
     * @throws EnvironmentException
     * @throws InvalidArgumentException
     */
    private function getQualifiedSetterData(
        mixed $value,
        KeyConfig $keyConfig,
        Data $fieldDefinition,
        int $storeId,
        mixed $storedSlot,
        bool $isPatch,
        callable $convert
    ): mixed {
        $supplied = $this->qualifiedValueSerializer->parseEntries($value, $keyConfig);
        $this->qualifiedValueSerializer->assertSupported(
            $supplied,
            $this->storeEngineResolver->getCapabilitiesOfStore($storeId)
        );

        $stored = $storedSlot === null
            ? []
            : $this->qualifiedValueSerializer->getVisibleEntries($storedSlot, $keyConfig);
        $current = $this->byQualifier($stored);

        $converted = [];
        foreach ($supplied as $qualifiedValue) {
            // a slot the row holds nothing at carries null, so that the current value handed over
            // is always the one entry's and never falls back to reading the whole list
            $currentValue = $current[$qualifiedValue->getQualifier()] ?? $qualifiedValue->withValue(null);

            $converted[] = $qualifiedValue->withValue(
                $this->getValidatedEntryValue($fieldDefinition, $convert($qualifiedValue->getValue(), $currentValue))
            );
        }

        return $this->assembleSlot(
            $isPatch ? $this->qualifiedValueSerializer->mergeEntries($stored, $converted) : $converted,
            $storedSlot === null ? [] : $this->qualifiedValueSerializer->getHiddenEntries($storedSlot, $keyConfig)
        );
    }

    /**
     * What the slot stores: the written entries followed by the hidden ones the row already holds.
     * The bare value where that is one default entry alone, which is the slot as it was stored
     * before any key declared anything.
     *
     * Nothing written and nothing hidden stores nothing, rather than an empty list: a key whose
     * every entry the write cleared holds what it held before any key declared anything, which is
     * no value, and a row left holding `[]` would publish a value a read then has to explain.
     *
     * @param QualifiedValue[] $written
     * @param QualifiedValue[] $hidden
     */
    private function assembleSlot(array $written, array $hidden): mixed
    {
        if ($hidden === [] && $written === []) {
            return null;
        }

        if ($hidden === [] && count($written) === 1 && $this->addressesDefaultSlot($written[0])) {
            return $written[0]->getValue();
        }

        return $this->qualifiedValueSerializer->toPayload(array_merge($written, $hidden));
    }

    /**
     * The current value the key's own type adapter sees for a key declaring nothing: the slot's own
     * value where it holds a plain one, handed over as null exactly as before, and the one default
     * entry's where the row holds a list the key no longer declares.
     *
     * Raw, for the reason the qualified arm's is raw
     * ({@see self::getQualifiedSetterData()}): where this hands over null the context reads the
     * slot itself, unnormalized, so handing a normalized value over here would make one arm of the
     * same read disagree with the other and with every adapter that normalizes the current value
     * itself.
     */
    private function getCurrentOfSingleValue(
        mixed $storedSlot,
        KeyConfig $keyConfig
    ): ?QualifiedValue {
        if (!$this->qualifiedValueSerializer->holdsEntries($storedSlot)) {
            return null;
        }

        $visible = $this->qualifiedValueSerializer->getVisibleEntries($storedSlot, $keyConfig);

        return $visible[0] ?? new QualifiedValue(null);
    }

    /**
     * One entry's own value, held to the key's type before it is wrapped into the list.
     *
     * {@see ValidateObjectDataTrait::validateEncryptedField()} reads one value of the key's type, so
     * it is asked per entry rather than of the list. A value that does not pass refuses the write:
     * the sibling arm, the key declaring nothing, skips the slot instead and keeps doing so - that
     * is what a key without this list has always done - but skipping one entry of a list would
     * leave the slot holding a shorter list than the write supplied, under a successful response.
     *
     * @throws InvalidArgumentException
     */
    private function getValidatedEntryValue(Data $fieldDefinition, mixed $value): mixed
    {
        if (!$this->validateEncryptedField($fieldDefinition, $value)) {
            throw new InvalidArgumentException('A value of the key did not come back in the key\'s own type.');
        }

        return $value;
    }

    /**
     * The raw value the row holds at one slot, which is both what the entries of a patch are merged
     * over, and where the entries the declaration hides are found.
     *
     * @throws DatabaseException
     */
    private function readStoredSlot(
        Classificationstore $container,
        int $groupId,
        int $keyId,
        string $language
    ): mixed {
        // a container the element does not hold yet: there is no row, so there is nothing stored
        if ($container->getObject() === null) {
            return null;
        }

        try {
            return $container->getLocalizedKeyValue($groupId, $keyId, $language, true, true);
        } catch (Exception $exception) {
            throw new DatabaseException($exception->getMessage(), previous: $exception);
        }
    }

    /**
     * @param QualifiedValue[] $values
     *
     * @return array<string, QualifiedValue>
     */
    private function byQualifier(array $values): array
    {
        $byQualifier = [];
        foreach ($values as $value) {
            $byQualifier[$value->getQualifier()] = $value;
        }

        return $byQualifier;
    }

    private function addressesDefaultSlot(QualifiedValue $value): bool
    {
        return $value->getVariant() === null
            && $value->getPosition() === QualifiedValueSerializerInterface::DEFAULT_POSITION;
    }

    private function cleanupStoreGroups(Classificationstore $container): void
    {
        $activeGroupIds = array_keys($container->getActiveGroups());
        $existingGroupIds = $container->getGroupIdsWithData();

        foreach ($existingGroupIds as $existingGroupId) {
            if (!in_array($existingGroupId, $activeGroupIds, true)) {
                $container->removeGroupData($existingGroupId);
            }
        }
    }

    /**
     * Returns group IDs that are marked for deletion in the store data.
     *
     * @return int[]
     */
    private function getDeletedGroupIds(array $store): array
    {
        $deletedGroupIds = [];

        foreach ($store as $groupId => $groupData) {
            if ($groupId === 'activeGroups' || $groupId === 'groupCollectionMapping') {
                continue;
            }

            if (is_array($groupData) && isset($groupData['action']) && $groupData['action'] === 'deleted') {
                $deletedGroupIds[] = (int) $groupId;
            }
        }

        return $deletedGroupIds;
    }

    private function getStoreDefinitions(
        Concrete $dataObject,
        ClassificationstoreDefinition $classificationStore
    ): array {
        $mapping = [];
        foreach ($classificationStore->recursiveGetActiveGroupsIds($dataObject) as $groupId => $active) {
            if (!$active) {
                continue;
            }

            $keys = $this->getClassificationStoreKeysFromGroup($groupId);
            foreach ($keys as $groupKey) {
                $definition = $this->serviceResolver->getFieldDefinitionFromKeyConfig($groupKey);
                if ($definition === null) {
                    continue;
                }
                $mapping[$groupKey->getGroupId()][$groupKey->getKeyId()] = $definition;
            }
        }

        return $mapping;
    }

    private function getValidLanguages(
        Concrete $object,
        bool $isStoreLocalized,
        string $permissionType = ElementPermissions::LANGUAGE_VIEW_PERMISSIONS,
        ?UserInterface $user = null
    ): array {
        $languages = [MappingProperty::NOT_LOCALIZED_KEY];
        if ($isStoreLocalized === false) {
            return $languages;
        }

        if ($user === null) {
            $user = $this->securityService->getCurrentUser();
        }

        if ($user->isAdmin()) {
            return array_merge($languages, $this->toolResolver->getValidLanguages());
        }

        return $this->languageService->getUserAllowedLanguagesWithLanguageIndependentValue(
            $object,
            $user,
            $permissionType
        );
    }

    /**
     * @return GroupConfig[]
     */
    private function getActiveGroupsConfig(array $activeGroups): array
    {
        $groups = [];
        foreach ($activeGroups as $groupId => $active) {
            if ($active) {
                $groupConfig = $this->groupConfigResolver->getById($groupId);
                if ($groupConfig) {
                    $groups[$groupId] = $groupConfig;
                }
            }
        }

        return $groups;
    }

    private function getActiveGroupsFromStore(array $store): array
    {
        $activeGroups = [];
        foreach ($store as $groupId => $groupData) {
            $activeGroups[$groupId] = true;
        }

        return $activeGroups;
    }

    /**
     * @return KeyGroupRelation[]
     */
    private function getClassificationStoreKeysFromGroup(int $groupId): array
    {
        return $this->keyRelationResolver->getByGroup($groupId);
    }

    /**
     * @throws DatabaseException
     */
    private function getResolvedValue(
        ClassificationstoreModel $classificationstore,
        int $groupId,
        KeyGroupRelation $key,
        string $language,
        bool $useComputedValue = false,
    ): mixed {
        $keyConfig = $this->definitionCacheResolver->get($key->getKeyId());
        if ($keyConfig === null) {
            return null;
        }

        if ($useComputedValue && $keyConfig->getType() === 'calculatedValue') {
            return $this->resolveComputedValue($classificationstore, $groupId, $key, $language, $keyConfig);
        }

        return $this->getValue($classificationstore, $groupId, $key, $language);
    }

    /**
     * @throws DatabaseException
     */
    private function resolveComputedValue(
        ClassificationstoreModel $classificationstore,
        int $groupId,
        KeyGroupRelation $key,
        string $language,
        KeyConfig $keyConfig,
    ): mixed {
        $childDef = $this->serviceResolver->getFieldDefinitionFromKeyConfig($keyConfig);
        if ($childDef === null) {
            return null;
        }

        $calculatedValue = new CalculatedValue($classificationstore->getFieldname());
        $calculatedValue->setContextualData(
            'classificationstore',
            $classificationstore->getFieldname(),
            null,
            $language,
            $groupId,
            $key->getKeyId(),
            $childDef,
        );

        return $this->dataObjectServiceResolver->getCalculatedFieldValue(
            $classificationstore->getObject(),
            $calculatedValue,
        );
    }

    /**
     * @throws DatabaseException
     */
    private function getPreviewValue(
        ClassificationstoreModel $classificationstore,
        int $groupId,
        KeyGroupRelation $key,
        string $language,
        array $data
    ): mixed {
        return $this->getValue($classificationstore, $groupId, $key, $language, $data, true);
    }

    /**
     * Retrieves normalized or preview values for classification store keys.
     *
     * @throws DatabaseException
     */
    private function getValue(
        ClassificationstoreModel $classificationstore,
        int $groupId,
        KeyGroupRelation $key,
        string $language,
        ?array $data = null,
        bool $isPreview = false
    ): mixed {
        try {
            $value = $classificationstore->getLocalizedKeyValue(
                $groupId,
                $key->getKeyId(),
                $language,
                true,
                true
            );
        } catch (Exception $exception) {
            // the read sibling of self::readStoredSlot(): the same slot, the same wrap, so the same
            // original type and stack trace survive it
            throw new DatabaseException($exception->getMessage(), previous: $exception);
        }

        $keyConfig = $this->definitionCacheResolver->get($key->getKeyId());
        if ($keyConfig === null) {
            return null;
        }

        $fieldDefinition = $this->serviceResolver->getFieldDefinitionFromKeyConfig($keyConfig);
        if ($fieldDefinition === null) {
            return null;
        }

        if ($value === null) {
            $value = $this->getDefaultValue($fieldDefinition);
        }

        $isPreviewRead = $isPreview && $data !== null;

        if ($this->keyQualifierResolver->declaresQualifiers($keyConfig)) {
            if ($value === null) {
                // the slot is left out of the payload, the way it is for a key declaring nothing
                return null;
            }

            if (!$isPreviewRead) {
                return $this->qualifiedValueSerializer->toPayload(
                    $this->normalizeEntries(
                        $this->qualifiedValueSerializer->getVisibleEntries($value, $keyConfig),
                        $fieldDefinition
                    )
                );
            }
        }

        // the declaration alone decides the shape: a key declaring nothing serializes one value,
        // and a flat label map holds one per key, so the default slot is the one either shows
        if ($value !== null) {
            $value = $this->qualifiedValueSerializer->getDefaultSlotValue($value, $keyConfig);
        }

        if ($isPreviewRead) {
            $data = $this->dataService->getPreviewFieldData($value, $fieldDefinition, $data);

            return $data[$this->dataService->getPreviewFieldName($fieldDefinition)];
        }

        return $this->dataService->getNormalizedValue($value, $fieldDefinition);
    }

    /**
     * @param QualifiedValue[] $values
     *
     * @return QualifiedValue[]
     */
    private function normalizeEntries(array $values, Data $fieldDefinition): array
    {
        $normalized = [];
        foreach ($values as $value) {
            $normalized[] = $value->withValue(
                $this->dataService->getNormalizedValue($value->getValue(), $fieldDefinition)
            );
        }

        return $normalized;
    }

    private function getDefaultValue(Data $fieldDefinition): ?QuantityValue
    {
        if (!$fieldDefinition instanceof InputQuantityValue && !$fieldDefinition instanceof QuantityValueDefinition) {
            return null;
        }

        $defaultValue = $fieldDefinition->getDefaultValue();
        $defaultUnit = $fieldDefinition->getDefaultUnit();

        if ($defaultValue || $defaultUnit) {
            return new QuantityValue($defaultValue, $defaultUnit);
        }

        return null;
    }
}
