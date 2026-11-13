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

use Closure;
use Exception;
use Pimcore\Bundle\GenericDataIndexBundle\Model\SearchIndexAdapter\MappingProperty;
use Pimcore\Bundle\StaticResolverBundle\Lib\ToolResolverInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\ClassificationStore\DefinitionCacheResolverInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\ClassificationStore\GroupConfigResolverInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\ClassificationStore\ServiceResolverInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\DataObjectServiceResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\KeyLocalizationResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\KeyQualifierResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\KeyRelationResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\KeySlot;
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
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\HttpResponseErrorKeys;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\ClassDefinition\Data\Classificationstore as ClassificationstoreDefinition;
use Pimcore\Model\DataObject\ClassDefinition\Data\InputQuantityValue;
use Pimcore\Model\DataObject\ClassDefinition\Data\MultiValue as MultiValueDefinition;
use Pimcore\Model\DataObject\ClassDefinition\Data\QuantityValue as QuantityValueDefinition;
use Pimcore\Model\DataObject\Classificationstore;
use Pimcore\Model\DataObject\Classificationstore as ClassificationstoreModel;
use Pimcore\Model\DataObject\Classificationstore\GroupConfig;
use Pimcore\Model\DataObject\Classificationstore\KeyConfig;
use Pimcore\Model\DataObject\Classificationstore\KeyGroupRelation;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\DataObject\Data\CalculatedValue;
use Pimcore\Model\DataObject\Data\MultiValue;
use Pimcore\Model\DataObject\Data\QuantityValue;
use Pimcore\Model\DataObject\Exception\UnsupportedEngineFeatureException;
use Pimcore\Model\UserInterface;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use function array_diff;
use function array_filter;
use function array_intersect;
use function array_is_list;
use function array_key_exists;
use function array_keys;
use function array_map;
use function array_merge;
use function array_unique;
use function array_values;
use function ctype_digit;
use function in_array;
use function is_array;
use function is_int;
use function is_string;
use function strpos;
use function substr;

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
        private KeyLocalizationResolverInterface $keyLocalizationResolver,
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
            $store = $this->foldVariantAddresses($fieldDefinition, $store);
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
        $keyLanguages = $this->createKeyLanguageResolver($object, $fieldDefinition->isLocalized());
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
                // an ancestor's value is resolved for a key declaring nothing alone: the entries of a
                // `multiValue` key beyond the one value of its default slot have no origin to report
                $resolveKeyValue = $resolveInheritedValue
                    && !$this->keyQualifierResolver->declaresQualifiers($keyConfig);

                foreach ($keyLanguages($keyConfig) as $language) {
                    $fieldContextData = new FieldContextData(
                        $container,
                        $language,
                        $groupId,
                        $groupKeyId,
                        resolveInheritedValue: $resolveKeyValue
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
        $keyLanguages = $this->createKeyLanguageResolver($value->getObject(), $fieldDefinition->isLocalized());
        $activeGroups = $this->getActiveGroupsConfig($value->getActiveGroups());
        $keysByGroup = $this->keyRelationResolver->getByGroups(array_keys($activeGroups));
        foreach ($activeGroups as $groupId => $groupConfig) {
            $keys = $keysByGroup[$groupId];
            $groupName = $groupConfig->getName();
            foreach ($keys as $key) {
                $keyConfig = $this->definitionCacheResolver->get($key->getKeyId());
                $isKeyLocalized = $this->isKeyLocalized($keyConfig, $fieldDefinition->isLocalized());
                foreach ($keyLanguages($keyConfig) as $validLanguage) {
                    $previewValue = $this->getPreviewValue($value, $groupId, $key, $validLanguage, $data);
                    $previewKey = $groupName . ' - ' . $key->getName() .
                        ($isKeyLocalized ? ' / ' . $validLanguage : null);
                    $data[$fieldName][$previewKey] = $previewValue;
                }
            }
        }

        return $data;
    }

    /**
     * The grid's address of a variant cell, `<keyId>#<variant>`, folded into the entry the key's own
     * id carries, so a patch of one variant cell is merged by qualifier like any other entry.
     *
     * @return array<array-key, mixed>
     *
     * @throws InvalidArgumentException
     */
    private function foldVariantAddresses(ClassificationstoreDefinition $definition, array $store): array
    {
        foreach ($store as $groupId => $groupData) {
            if (!is_int($groupId) || !is_array($groupData)) {
                continue;
            }

            foreach ($groupData as $language => $keys) {
                if (is_array($keys)) {
                    $store[$groupId][$language] = $this->foldBucketAddresses($definition, $keys);
                }
            }
        }

        return $store;
    }

    /**
     * @param array<array-key, mixed> $keys one (group, language) bucket
     *
     * @return array<array-key, mixed>
     *
     * @throws InvalidArgumentException
     */
    private function foldBucketAddresses(ClassificationstoreDefinition $definition, array $keys): array
    {
        foreach ($keys as $address => $value) {
            $separator = is_string($address) ? strpos($address, '#') : false;
            if ($separator === false) {
                continue;
            }

            $keyId = $this->getDeclaringKeyId($definition, substr($address, 0, $separator));

            $variant = substr($address, $separator + 1);

            $held = $keys[$keyId] ?? [];
            $held = is_array($held) && array_is_list($held) && ($held === [] || is_array($held[0]))
                ? $held
                : [['v' => $held]];
            foreach ($this->getAddressedEntries($value) as $entry) {
                $entry['var'] = $variant;
                $held[] = $entry;
            }

            unset($keys[$address]);
            $keys[$keyId] = $held;
        }

        return $keys;
    }

    /**
     * The entries one variant cell's patch carries: a repeating key's cell is a list of
     * `{v, p}` entries, any other cell one entry object or a bare value.
     *
     * @return array<int, array<array-key, mixed>>
     */
    private function getAddressedEntries(mixed $value): array
    {
        if (!is_array($value)) {
            return [['v' => $value]];
        }

        if (array_key_exists('v', $value)) {
            return [$value];
        }

        $isEntry = static fn (mixed $item): bool => is_array($item) && array_key_exists('v', $item);
        $isEntryList = $value !== [] && array_is_list($value) && array_filter($value, $isEntry) === $value;

        return $isEntryList ? $value : [['v' => $value]];
    }

    /**
     * @throws InvalidArgumentException when the address names no key, or one that declares no variants
     */
    private function getDeclaringKeyId(ClassificationstoreDefinition $definition, string $keyId): int
    {
        if (!ctype_digit($keyId)
            || !$this->keyQualifierResolver->declaresQualifiers($definition->getKeyConfiguration((int) $keyId))
        ) {
            throw new InvalidArgumentException(
                'The value names a variant the key does not declare.',
                errorKey: HttpResponseErrorKeys::UNDECLARED_VARIANT->value
            );
        }

        return (int) $keyId;
    }

    /**
     * The stored store with the supplied one written over it.
     *
     * `array_replace_recursive()` merges two arrays element by element, which is what the group,
     * language and key buckets need and the wrong thing for the list a `multiValue` key's value
     * travels as: a write naming one entry would land on the first element of the stored list and
     * keep the rest of it by position. A `multiValue` key therefore carries the write's own entries
     * through unmerged, and they are merged by qualifier in {@see self::setQualifiedValues()}, which
     * reads what the store holds rather than `$elementStore` - the narrower view built under the
     * user's language *view* permissions and only over the groups currently active.
     *
     * Which keys the patch carries at all is {@see self::patchQualifiedSlots()}.
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
     * The `multiValue` keys of one (group, language) bucket, held to what the write actually named.
     *
     * A key the write names carries the write's own entries, unmerged. One it does not name is left
     * out of the patch altogether rather than carried in from `$elementStore`: there is no write to
     * make for it, and the store already holds it.
     *
     * Which keys those are is decided by the key's type alone, as it is in
     * {@see self::processGroupKeys()}: any other key is merged like any other single value.
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

        $keyLanguages = $this->createKeyLanguageResolver(
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
            foreach ($keys as $key) {
                foreach ($keyLanguages($this->definitionCacheResolver->get($key->getKeyId())) as $validLanguage) {
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

        $keyLanguages = $this->createKeyLanguageResolver(
            $element,
            $definition->isLocalized(),
            ElementPermissions::LANGUAGE_EDIT_PERMISSIONS,
            $user
        );
        $editableLanguages = $keyLanguages(null);

        foreach ($store as $groupId => $groupData) {
            foreach ($groupData as $language => $keys) {
                $isWritten = $this->processGroupKeys(
                    $element,
                    $user,
                    $definition,
                    $container,
                    $language,
                    $groupId,
                    $keys,
                    $isPatch,
                    $keyLanguages
                );
                if ($isWritten || in_array($language, $editableLanguages, true)) {
                    $activeGroups[$groupId] = true;
                }
            }
        }

        $container->setActiveGroups($activeGroups);
    }

    /**
     * @param Closure(?KeyConfig): string[] $keyLanguages
     *
     * @return bool whether any key of the bucket was written
     *
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
        bool $isPatch,
        Closure $keyLanguages
    ): bool {
        $relations = $this->getClassificationStoreKeysFromGroup($groupId);
        $isWritten = false;
        foreach ($keys as $keyId => $value) {
            $keyConfig = $definition->getKeyConfiguration($keyId);
            if (!in_array($language, $keyLanguages($keyConfig), true)) {
                continue;
            }

            $isWritten = true;
            $relation = $this->findRelation($relations, $keyId);
            // an assignment is converted and validated against its own effective definition
            $fieldDefinition = $relation !== null
                ? $this->getEffectiveFieldDefinition($relation)
                : $this->serviceResolver->getFieldDefinitionFromKeyConfig($keyConfig);

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

            if ($this->keyQualifierResolver->declaresQualifiers($keyConfig)) {
                $this->setQualifiedValues(
                    new KeySlot($container, $definition->getStoreId(), $groupId, $keyId, $language),
                    $value,
                    $keyConfig,
                    $fieldDefinition,
                    $isPatch,
                    $convert
                );

                continue;
            }

            // the key declares no qualifier, so the slot holds one value of the key's own type
            $converted = $convert($value, null, $isPatch);

            if (!$this->validateEncryptedField($fieldDefinition, $converted)) {
                continue;
            }

            $container->setLocalizedKeyValue($groupId, $keyId, $converted, $language);
        }

        return $isWritten;
    }

    /**
     * Writes the values of a `multiValue` key in one language through core's `setKeyValues()`, each
     * value taken through the adapter of the key's own type, one entry per slot value.
     *
     * A patch merges the supplied entries over the stored ones by qualifier and never by position
     * in the list, so an entry the write does not name survives as itself. Anything else replaces
     * the key in this language: a slot the write does not name is cleared, and the other languages
     * are slots of their own that the write does not address.
     *
     * The engine is asked about the supplied entries alone rather than about the merged list: the
     * stored ones were accepted when they were written, and holding this write to them would make a
     * key uneditable on an engine that no longer reports what it once did.
     *
     * Only the supplied entries go through the key's own type adapter, and never as a patch of
     * anything: what each entry carries is one whole value of the key's type. A value failing the
     * key's type refuses the write, since a slot dropped under a successful response is data lost
     * without an answer.
     *
     * @param callable(mixed, ?QualifiedValue): mixed $convert
     *
     * @throws DatabaseException
     * @throws EnvironmentException
     * @throws InvalidArgumentException
     */
    private function setQualifiedValues(
        KeySlot $slot,
        mixed $value,
        KeyConfig $keyConfig,
        Data $fieldDefinition,
        bool $isPatch,
        callable $convert
    ): void {
        $supplied = $this->qualifiedValueSerializer->parseEntries($value, $keyConfig);
        $this->qualifiedValueSerializer->assertSupported(
            $supplied,
            $this->storeEngineResolver->getCapabilitiesOfStore($slot->getStoreId())
        );

        $container = $slot->getContainer();
        $language = $slot->getLanguage();

        $stored = $container->getObject() === null
            ? []
            : $this->qualifiedValueSerializer->readStoredEntries(
                $container,
                $slot->getGroupId(),
                $slot->getKeyId(),
                $language,
                $keyConfig
            );
        $current = $this->byQualifier($stored);

        $converted = [];
        foreach ($supplied as $qualifiedValue) {
            // a slot the row holds nothing at carries null, so that the current value handed over
            // is always the one entry's
            $currentValue = $current[$qualifiedValue->getQualifier()] ?? $qualifiedValue->withValue(null);

            $converted[] = $qualifiedValue->withValue(
                $this->getValidatedEntryValue($fieldDefinition, $convert($qualifiedValue->getValue(), $currentValue))
            );
        }

        $entries = $isPatch ? $this->qualifiedValueSerializer->mergeEntries($stored, $converted) : $converted;

        $coreEntries = [];
        foreach ($entries as $entry) {
            $coreEntries[] = new MultiValue($entry->getValue(), $language, $entry->getVariant(), $entry->getPosition());
        }

        if (!$isPatch) {
            $named = array_map(static fn (QualifiedValue $entry): ?string => $entry->getVariant(), $entries);
            $held = array_map(static fn (QualifiedValue $entry): ?string => $entry->getVariant(), $stored);

            // Clearing addresses the VARIANT and never a position inside it, because a slot IS a
            // (language, variant) pair and core replaces one whole: for a key declaring a list
            // {@see Classificationstore::storeSlot()} drops the entire
            // `[$language][$variant]` bucket before writing back the positions the write named, so
            // every position the write dropped is already gone - a stored list at positions 0 and 1
            // followed by a write naming position 0 alone keeps nothing at position 1. What needs
            // saying is only the variant the write named NOTHING at, which core is never handed and
            // so never replaces.
            //
            // A null per omitted position would also be strictly worse than this: it makes the write
            // name the positions it is dropping, and {@see Classificationstore::resolveSlots()} asks
            // the engine to address the highest position a slot names. Shortening a held list would
            // then demand the list capability the write itself does not need, which is the same
            // engine-no-longer-reports case the paragraph above refuses to hold this write to.
            foreach (array_unique(array_diff($held, $named)) as $variant) {
                $coreEntries[] = new MultiValue(null, $language, $variant);
            }
        }

        if ($coreEntries !== []) {
            $this->writeKeyValues($slot, $coreEntries);
        }
    }

    /**
     * The one core call of the qualified write path, with what it can refuse answered the way the
     * sibling read of the same slots answers it rather than left to fail the whole object save.
     *
     * An engine that cannot hold what the write addresses is the 422 the capability guard raises for
     * the same write ({@see QualifiedValueSerializerInterface::assertSupported()}), reached where the
     * guard's view of the engine - the capabilities the store reports - and core's - what the
     * engine's own DAO supports - disagree. Anything else is a database failure and is wrapped as
     * {@see self::getValue()} wraps the read's.
     *
     * Core's own refusal of entries that do not fit the declaration
     * ({@see Classificationstore::setKeyValues()} throws `InvalidConfigException` for an undeclared
     * variant, a duplicate qualifier or a position the key does not permit) is deliberately not
     * answered as a client error here: every one of those is refused at the request boundary first
     * ({@see QualifiedValueParserInterface::parseEntries()}), so core raising one means Studio and
     * core read the key's declaration differently - which is not something the request can correct.
     *
     * The declared `maximum` is NOT one of those refusals, and is deliberately not held at the
     * request boundary either: core's setter stores the list, and core's own validation reports a
     * slot holding more values than the key declares when the object is saved
     * ({@see Data\Classificationstore::checkValidity()}, one field-level failure per slot), which the
     * save path answers as the 422 of a field validation failure. The rule there counts the values a
     * slot HOLDS rather than the position they sit at, so holding a position to the maximum here
     * would refuse writes core accepts.
     *
     * @param MultiValue[] $entries
     *
     * @throws DatabaseException
     * @throws InvalidArgumentException
     */
    private function writeKeyValues(KeySlot $slot, array $entries): void
    {
        try {
            $slot->getContainer()->setKeyValues($slot->getGroupId(), $slot->getKeyId(), $entries);
        } catch (UnsupportedEngineFeatureException $exception) {
            throw new InvalidArgumentException(
                $exception->getMessage(),
                previous: $exception,
                errorKey: HttpResponseErrorKeys::ENGINE_CAPABILITY_MISSING->value
            );
        } catch (Exception $exception) {
            throw new DatabaseException($exception->getMessage(), previous: $exception);
        }
    }

    /**
     * One entry's own value, held to the key's type before it is stored.
     *
     * {@see ValidateObjectDataTrait::validateEncryptedField()} reads one value of the key's type, so
     * it is asked of the type the `multiValue` key wraps. A value that does not pass refuses the
     * write: the sibling arm, the key declaring nothing, skips the slot instead and keeps doing so,
     * but skipping one entry of a list would leave the key holding fewer values than the write
     * supplied, under a successful response.
     *
     * @throws InvalidArgumentException
     */
    private function getValidatedEntryValue(Data $fieldDefinition, mixed $value): mixed
    {
        $valueDefinition = $fieldDefinition instanceof MultiValueDefinition
            ? ($fieldDefinition->getDelegate() ?? $fieldDefinition)
            : $fieldDefinition;

        if (!$valueDefinition instanceof Data || !$this->validateEncryptedField($valueDefinition, $value)) {
            throw new InvalidArgumentException('A value of the key did not come back in the key\'s own type.');
        }

        return $value;
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
                $definition = $this->getEffectiveFieldDefinition($groupKey);
                if ($definition === null) {
                    continue;
                }
                $mapping[$groupKey->getGroupId()][$groupKey->getKeyId()] = $definition;
            }
        }

        return $mapping;
    }

    /**
     * The languages of one key, memoized per language set: the key's own `localized` flag decides the
     * set, and a key stating none follows the class definition field. A key without a configuration
     * (null) is the field's.
     *
     * @return Closure(?KeyConfig): string[]
     */
    private function createKeyLanguageResolver(
        Concrete $object,
        bool $isStoreLocalized,
        string $permissionType = ElementPermissions::LANGUAGE_VIEW_PERMISSIONS,
        ?UserInterface $user = null
    ): Closure {
        $sets = [];

        return function (?KeyConfig $keyConfig) use (
            &$sets,
            $object,
            $isStoreLocalized,
            $permissionType,
            $user
        ): array {
            $isKeyLocalized = $this->isKeyLocalized($keyConfig, $isStoreLocalized);
            $setKey = $isKeyLocalized ? 'localized' : 'independent';
            if (!array_key_exists($setKey, $sets)) {
                // a key flagged `false` in a localized field is still held to the user's permission
                $permitted = $this->getValidLanguages(
                    $object,
                    $isKeyLocalized || $isStoreLocalized,
                    $permissionType,
                    $user
                );
                $sets[$setKey] = $isKeyLocalized
                    ? $permitted
                    : array_values(array_intersect([MappingProperty::NOT_LOCALIZED_KEY], $permitted));
            }

            return $sets[$setKey];
        };
    }

    private function isKeyLocalized(?KeyConfig $keyConfig, bool $isStoreLocalized): bool
    {
        $isKeyLocalized = $keyConfig === null ? null : $this->keyLocalizationResolver->getLocalized($keyConfig);

        return $isKeyLocalized ?? $isStoreLocalized;
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
     * Built on every call from the assignment's own effective definition, never from a memo held per key.
     */
    private function getEffectiveFieldDefinition(KeyGroupRelation $relation): ?Data
    {
        return $this->serviceResolver->getFieldDefinitionFromJson(
            $relation->getEffectiveDefinitionData(),
            $relation->getType()
        );
    }

    /**
     * @param KeyGroupRelation[] $relations
     */
    private function findRelation(array $relations, int $keyId): ?KeyGroupRelation
    {
        foreach ($relations as $relation) {
            if ($relation->getKeyId() === $keyId) {
                return $relation;
            }
        }

        return null;
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
        $childDef = $this->getEffectiveFieldDefinition($key);
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
            // the same wrap the qualified read makes, so the original type and stack trace survive it
            throw new DatabaseException($exception->getMessage(), previous: $exception);
        }

        $keyConfig = $this->definitionCacheResolver->get($key->getKeyId());
        if ($keyConfig === null) {
            return null;
        }

        $fieldDefinition = $this->getEffectiveFieldDefinition($key);
        if ($fieldDefinition === null) {
            return null;
        }

        $isPreviewRead = $isPreview && $data !== null;

        if (!$this->keyQualifierResolver->declaresQualifiers($keyConfig)) {
            // the key's configured default, as it has always been applied: to a slot the row holds
            // nothing at.
            //
            // A `multiValue` key is left out of this on purpose, and its declaration says so: its
            // values are the slots core holds, read below, and a key holding none publishes no
            // entry. Publishing the configured default of its inner type would invent an entry at
            // the default slot that the row does not hold, which the next write of the key would
            // store as a value nobody entered.
            if ($value === null) {
                $value = $this->getDefaultValue($fieldDefinition);
            }
        } else {
            $entries = $this->qualifiedValueSerializer->readStoredEntries(
                $classificationstore,
                $groupId,
                $key->getKeyId(),
                $language,
                $keyConfig
            );

            if ($entries === []) {
                // the slot is left out of the payload, the way it is for a key declaring nothing
                return null;
            }

            if (!$isPreviewRead) {
                return $this->qualifiedValueSerializer->toPayload(
                    $this->normalizeEntries($entries, $fieldDefinition)
                );
            }

            $value = $this->qualifiedValueSerializer->getDefaultSlotValue(
                $this->qualifiedValueSerializer->toPayload($entries)
            );
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
