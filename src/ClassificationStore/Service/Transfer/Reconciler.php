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

namespace Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\Transfer;

use Closure;
use Doctrine\DBAL\Connection;
use Generator;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Repository\Configuration\CollectionRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Repository\Configuration\CollRelationRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Repository\Configuration\GroupRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Repository\Configuration\KeyGroupRelationRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Repository\Configuration\KeyRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Repository\Configuration\StoreRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Repository\Configuration\SubGroupRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Util\Constant\ApplyOutcome;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Util\Constant\ArtifactField;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Util\Constant\ArtifactSection;
use Pimcore\Model\DataObject\Classificationstore\CollectionConfig;
use Pimcore\Model\DataObject\Classificationstore\GroupConfig;
use Pimcore\Model\DataObject\Classificationstore\KeyConfig;
use Pimcore\Model\DataObject\Classificationstore\KeyGroupRelation;
use Pimcore\Model\DataObject\Classificationstore\StoreConfig;
use Pimcore\Model\DataObject\Classificationstore\SubGroupConfig;
use Throwable;
use function array_keys;
use function count;
use function explode;
use function implode;
use function in_array;
use function is_array;
use function is_string;
use function json_decode;
use function mb_strtolower;
use function sprintf;

/**
 * @internal
 */
final readonly class Reconciler implements ReconcilerInterface
{
    private const int CHUNK_SIZE = 200;

    /**
     * What a `definitionOverride` may carry, mirroring the closed list the model enforces
     * ({@see KeyGroupRelation::setDefinitionOverride()}). It is restated here because a reconcile
     * has to name every property it refuses rather than stop at the first one the model throws on.
     */
    private const array OVERRIDABLE_PROPERTIES = ['options', 'defaultUnit', 'validUnits'];

    /**
     * What an entry of each section is called in the report.
     */
    private const array SECTION_LEVELS = [
        ArtifactSection::STORE->value => 'store',
        ArtifactSection::SUB_GROUPS->value => 'sub group',
        ArtifactSection::COLLECTIONS->value => 'collection',
        ArtifactSection::GROUPS->value => 'group',
        ArtifactSection::KEYS->value => 'key',
        ArtifactSection::COLLECTION_GROUP_RELATIONS->value => 'collection group relation',
        ArtifactSection::KEY_GROUP_RELATIONS->value => 'key assignment',
    ];

    public function __construct(
        private ArtifactParserInterface $artifactParser,
        private CollectionRepositoryInterface $collectionRepository,
        private CollRelationRepositoryInterface $collectionRelationRepository,
        private Connection $connection,
        private GroupRepositoryInterface $groupRepository,
        private KeyGroupRelationRepositoryInterface $keyGroupRelationRepository,
        private KeyRepositoryInterface $keyRepository,
        private StoreRepositoryInterface $storeRepository,
        private SubGroupRepositoryInterface $subGroupRepository,
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function apply(
        ArtifactDocument $document,
        ?StoreConfig $store,
        bool $dryRun,
        ?Closure $onDecision = null,
    ): ApplyReport {
        $context = new ApplyContext($dryRun, new ApplyReport(), $onDecision);

        // a dry run writes nothing, so it needs nothing to roll back
        $ownTransaction = !$dryRun && !$this->connection->isTransactionActive();
        if ($ownTransaction) {
            $this->connection->beginTransaction();
        }

        try {
            $this->applyStore($context, $document, $store);
            $this->applySubGroups($context, $document);
            $this->applyCollections($context, $document);
            $this->applyGroups($context, $document);
            $this->applyKeys($context, $document);
            $this->applyCollectionRelations($context, $document);
            $this->applyKeyAssignments($context, $document);
        } catch (Throwable $e) {
            // Throwable, not Exception: an Error has to leave the store as it was just the same
            if ($ownTransaction && $this->connection->isTransactionActive()) {
                $this->connection->rollBack();
            }

            throw $e;
        }

        $report = $context->getReport();

        if ($ownTransaction) {
            $report->hasConflicts() ? $this->connection->rollBack() : $this->connection->commit();
        }

        return $report;
    }

    private function applyStore(ApplyContext $context, ArtifactDocument $document, ?StoreConfig $store): void
    {
        $identifier = $document->getStoreIdentifier();
        $name = $document->getStoreName();
        $description = $document->getStoreDescription();
        $label = $this->label(ArtifactSection::STORE, $name);

        if ($store === null) {
            // only the console command gets here; the endpoint always has the store of its route
            $context->record(ApplyOutcome::CREATED, $label);

            if (!$context->isDryRun()) {
                $created = $this->storeRepository->saveFromArtifact(null, $identifier, $name, $description);
                $context->setStoreId($created->getId());
            }

            return;
        }

        $context->setStoreId($store->getId());

        $unchanged = $this->describesSameEntity(
            $store->getName(),
            $store->getDescription(),
            $name,
            $description
        );

        $outcome = match (true) {
            $store->getIdentifier() === null => ApplyOutcome::ADOPTED,
            $unchanged => ApplyOutcome::MATCHED_UNCHANGED,
            default => ApplyOutcome::UPDATED,
        };

        $context->record($outcome, $label);

        if ($outcome !== ApplyOutcome::MATCHED_UNCHANGED && !$context->isDryRun()) {
            $this->storeRepository->saveFromArtifact($store, $identifier, $name, $description);
        }
    }

    private function applySubGroups(ApplyContext $context, ArtifactDocument $document): void
    {
        $storeId = (int) $context->getStoreId();

        $this->reconcileNamedEntities(
            $context,
            ArtifactSection::SUB_GROUPS,
            $document,
            fn (int $afterId, int $limit): array => $this->subGroupRepository
                ->getChunkAfterId($storeId, $afterId, $limit),
            fn (?object $target, string $identifier, string $name, ?string $description): int => (int) $this
                ->subGroupRepository
                ->saveFromArtifact(
                    $target instanceof SubGroupConfig ? $target : null,
                    $storeId,
                    $identifier,
                    $name,
                    $description
                )
                ->getId()
        );
    }

    private function applyCollections(ApplyContext $context, ArtifactDocument $document): void
    {
        $storeId = (int) $context->getStoreId();

        $this->reconcileNamedEntities(
            $context,
            ArtifactSection::COLLECTIONS,
            $document,
            fn (int $afterId, int $limit): array => $this->collectionRepository
                ->getChunkAfterId($storeId, $afterId, $limit),
            fn (?object $target, string $identifier, string $name, ?string $description): int => (int) $this
                ->collectionRepository
                ->saveFromArtifact(
                    $target instanceof CollectionConfig ? $target : null,
                    $storeId,
                    $identifier,
                    $name,
                    $description
                )
                ->getId()
        );
    }

    private function applyGroups(ApplyContext $context, ArtifactDocument $document): void
    {
        $storeId = (int) $context->getStoreId();

        $this->reconcileNamedEntities(
            $context,
            ArtifactSection::GROUPS,
            $document,
            fn (int $afterId, int $limit): array => $this->groupRepository
                ->getChunkAfterId($storeId, $afterId, $limit),
            fn (?object $target, string $identifier, string $name, ?string $description): int => (int) $this
                ->groupRepository
                ->saveFromArtifact(
                    $target instanceof GroupConfig ? $target : null,
                    $storeId,
                    $identifier,
                    $name,
                    $description
                )
                ->getId()
        );
    }

    /**
     * The entity sections that carry a name and a description only, which is sub groups,
     * collections and groups.
     *
     * @param Closure(int, int): array<int, SubGroupConfig|CollectionConfig|GroupConfig> $walk
     * @param Closure(?object, string, string, ?string): int $save
     */
    private function reconcileNamedEntities(
        ApplyContext $context,
        ArtifactSection $section,
        ArtifactDocument $document,
        Closure $walk,
        Closure $save,
    ): void {
        $entries = $document->getEntries($section);
        $entriesByName = $this->indexByName($entries, ArtifactField::NAME->value);
        $matched = [];

        foreach ($this->walkTargetByIdCursor($context, $walk) as $target) {
            $entryKey = $this->matchEntryKey($entries, $entriesByName, $target->getIdentifier(), $target->getName());

            if ($entryKey === null) {
                $this->recordUnmatchedTarget($context, $section, $entriesByName, $target);

                continue;
            }

            $matched[$entryKey] = true;
            $this->writeNamedEntity($context, $section, $target, $entries[$entryKey], $save);
        }

        foreach ($entries as $entryKey => $entry) {
            if (isset($matched[$entryKey])) {
                continue;
            }

            $this->writeNamedEntity($context, $section, null, $entry, $save);
        }
    }

    /**
     * @param array<string, mixed> $entry
     * @param Closure(?object, string, string, ?string): int $save
     */
    private function writeNamedEntity(
        ApplyContext $context,
        ArtifactSection $section,
        SubGroupConfig|CollectionConfig|GroupConfig|null $target,
        array $entry,
        Closure $save,
    ): void {
        $identifier = (string) $entry[ArtifactField::IDENTIFIER->value];
        $name = (string) $entry[ArtifactField::NAME->value];
        $description = $this->readNullableString($entry, ArtifactField::DESCRIPTION->value);

        $unchanged = $target !== null && $this->describesSameEntity(
            $target->getName(),
            $target->getDescription(),
            $name,
            $description
        );

        $outcome = match (true) {
            $target === null => ApplyOutcome::CREATED,
            $target->getIdentifier() === null => ApplyOutcome::ADOPTED,
            $unchanged => ApplyOutcome::MATCHED_UNCHANGED,
            default => ApplyOutcome::UPDATED,
        };

        $context->record($outcome, $this->label($section, $name));

        $id = $target?->getId();

        if ($outcome !== ApplyOutcome::MATCHED_UNCHANGED && !$context->isDryRun()) {
            $id = $save($target, $identifier, $name, $description);
        }

        if ($id !== null) {
            $context->register($section, $identifier, $id);
        }
    }

    private function applyKeys(ApplyContext $context, ArtifactDocument $document): void
    {
        $storeId = (int) $context->getStoreId();
        $entries = $document->getEntries(ArtifactSection::KEYS);
        $entriesByName = $this->indexByName($entries, ArtifactField::NAME->value);
        $matched = [];

        // the disabled rows first: a key the file enables moves into the walk that follows, where
        // the entry it has already answered for is skipped
        foreach ([true, false] as $disabledOnly) {
            $walk = fn (int $afterId, int $limit): array => $this->keyRepository
                ->getChunkAfterId($storeId, $disabledOnly, $afterId, $limit);

            foreach ($this->walkTargetByIdCursor($context, $walk) as $target) {
                $entryKey = $this->matchEntryKey(
                    $entries,
                    $entriesByName,
                    $target->getIdentifier(),
                    $target->getName()
                );

                if ($entryKey === null) {
                    $this->recordUnmatchedTarget($context, ArtifactSection::KEYS, $entriesByName, $target);

                    continue;
                }

                if (isset($matched[$entryKey])) {
                    continue;
                }

                $matched[$entryKey] = true;
                $this->writeKey($context, $target, $entries[$entryKey]);
            }
        }

        foreach ($entries as $entryKey => $entry) {
            if (isset($matched[$entryKey])) {
                continue;
            }

            $this->writeKey($context, null, $entry);
        }
    }

    /**
     * @param array<string, mixed> $entry
     */
    private function writeKey(ApplyContext $context, ?KeyConfig $target, array $entry): void
    {
        $keyEntry = $this->artifactParser->readKeyEntry($entry);
        $label = $this->label(ArtifactSection::KEYS, $keyEntry->getName());

        if (!$keyEntry->isEnabled()) {
            $context->markKeyDisabled($keyEntry->getIdentifier());
        }

        $outcome = match (true) {
            $target === null => ApplyOutcome::CREATED,
            $target->getIdentifier() === null => ApplyOutcome::ADOPTED,
            $this->describesSameKey($target, $keyEntry) => ApplyOutcome::MATCHED_UNCHANGED,
            default => ApplyOutcome::UPDATED,
        };

        if ($outcome !== ApplyOutcome::MATCHED_UNCHANGED
            && $target !== null
            && !$keyEntry->isEnabled()
            && $this->refuseDisablingAnAssignedKey($context, $target, $keyEntry->getName())) {
            return;
        }

        $context->record($outcome, $label);

        $id = $target?->getId();

        if ($outcome !== ApplyOutcome::MATCHED_UNCHANGED && !$context->isDryRun()) {
            $id = $this->keyRepository->saveFromArtifact($target, (int) $context->getStoreId(), $keyEntry)->getId();
        }

        if ($id !== null) {
            $context->register(ArtifactSection::KEYS, $keyEntry->getIdentifier(), $id);
        }
    }

    /**
     * Writing `enabled = 0` deletes every assignment of the key
     * ({@see \Pimcore\Model\DataObject\Classificationstore\KeyConfig\Dao::update()}), so a file
     * that disables a key the target still assigns would untag groups it says nothing about.
     * Refused, naming those groups.
     */
    private function refuseDisablingAnAssignedKey(ApplyContext $context, KeyConfig $target, string $name): bool
    {
        $groups = $this->keyGroupRelationRepository->getGroupNamesByKeyId((int) $target->getId());

        if ($groups === []) {
            return false;
        }

        $context->record(
            ApplyOutcome::CONFLICT,
            sprintf(
                'key "%s" is disabled by the file but still assigned to the group(s) "%s"',
                $name,
                implode('", "', $groups)
            )
        );

        return true;
    }

    private function applyCollectionRelations(ApplyContext $context, ArtifactDocument $document): void
    {
        $section = ArtifactSection::COLLECTION_GROUP_RELATIONS;
        $storeId = (int) $context->getStoreId();
        $entries = $document->getEntries($section);
        $matched = [];

        $walk = fn (int $limit, int $offset): array => $this->collectionRelationRepository
            ->getChunkByStore($storeId, $limit, $offset);

        foreach ($this->walkTargetByOffset($context, $walk) as $scoped) {
            $entryKey = $scoped->getCollectionIdentifier()
                . ArtifactParserInterface::ENTRY_KEY_SEPARATOR
                . $scoped->getGroupIdentifier();
            $label = $this->relationLabel($section, $entryKey);

            if (!isset($entries[$entryKey])) {
                $context->record(ApplyOutcome::TARGET_ONLY, $label);

                continue;
            }

            $matched[$entryKey] = true;
            $sorter = $this->artifactParser->readSorter($entries[$entryKey], $section);
            $relation = $scoped->getRelation();

            if ($relation->getSorter() === $sorter) {
                $context->record(ApplyOutcome::MATCHED_UNCHANGED, $label);

                continue;
            }

            $context->record(ApplyOutcome::UPDATED, $label);

            if (!$context->isDryRun()) {
                $this->collectionRelationRepository->createOrUpdate(
                    $relation->getColId(),
                    $relation->getGroupId(),
                    $sorter
                );
            }
        }

        foreach ($entries as $entryKey => $entry) {
            if (isset($matched[$entryKey])) {
                continue;
            }

            $this->createCollectionRelation($context, $entryKey, $entry);
        }
    }

    /**
     * @param array<string, mixed> $entry
     */
    private function createCollectionRelation(ApplyContext $context, string $entryKey, array $entry): void
    {
        $section = ArtifactSection::COLLECTION_GROUP_RELATIONS;
        $label = $this->relationLabel($section, $entryKey);
        $collectionId = $context->resolve(
            ArtifactSection::COLLECTIONS,
            (string) $entry[ArtifactField::COLLECTION->value]
        );
        $groupId = $context->resolve(ArtifactSection::GROUPS, (string) $entry[ArtifactField::GROUP->value]);

        if ($collectionId === null || $groupId === null) {
            $this->recordUnresolvedEndpoints($context, $label);

            return;
        }

        $context->record(ApplyOutcome::CREATED, $label);

        if (!$context->isDryRun()) {
            $this->collectionRelationRepository->createOrUpdate(
                $collectionId,
                $groupId,
                $this->artifactParser->readSorter($entry, $section)
            );
        }
    }

    private function applyKeyAssignments(ApplyContext $context, ArtifactDocument $document): void
    {
        $section = ArtifactSection::KEY_GROUP_RELATIONS;
        $storeId = (int) $context->getStoreId();
        $entries = $document->getEntries($section);
        $matched = [];

        $walk = fn (int $limit, int $offset): array => $this->keyGroupRelationRepository
            ->getChunkByStore($storeId, $limit, $offset);

        foreach ($this->walkTargetByOffset($context, $walk) as $scoped) {
            $entryKey = $scoped->getGroupIdentifier()
                . ArtifactParserInterface::ENTRY_KEY_SEPARATOR
                . $scoped->getKeyIdentifier();

            if (!isset($entries[$entryKey])) {
                $context->record(ApplyOutcome::TARGET_ONLY, $this->relationLabel($section, $entryKey));

                continue;
            }

            $matched[$entryKey] = true;
            $this->writeKeyAssignment($context, $scoped->getRelation(), $entries[$entryKey], $entryKey);
        }

        foreach ($entries as $entryKey => $entry) {
            if (isset($matched[$entryKey])) {
                continue;
            }

            $this->writeKeyAssignment($context, null, $entry, $entryKey);
        }
    }

    /**
     * @param array<string, mixed> $entry
     */
    private function writeKeyAssignment(
        ApplyContext $context,
        ?KeyGroupRelation $target,
        array $entry,
        string $entryKey,
    ): void {
        $section = ArtifactSection::KEY_GROUP_RELATIONS;
        $assignment = $this->artifactParser->readAssignmentEntry($entry);
        $label = $this->relationLabel($section, $entryKey);

        if ($this->refuseAssignment($context, $assignment, $label)) {
            return;
        }

        $groupId = $target?->getGroupId()
            ?? $context->resolve(ArtifactSection::GROUPS, $assignment->getGroupIdentifier());
        $keyId = $target?->getKeyId()
            ?? $context->resolve(ArtifactSection::KEYS, $assignment->getKeyIdentifier());

        if ($groupId === null || $keyId === null) {
            $this->recordUnresolvedEndpoints($context, $label);

            return;
        }

        if ($target !== null && $this->describesSameAssignment($target, $assignment)) {
            $context->record(ApplyOutcome::MATCHED_UNCHANGED, $label);

            return;
        }

        $context->record($target === null ? ApplyOutcome::CREATED : ApplyOutcome::UPDATED, $label);

        if (!$context->isDryRun()) {
            $this->keyGroupRelationRepository->createOrUpdateFromArtifact($assignment, $keyId, $groupId);
        }
    }

    /**
     * The three refusals an assignment carries on its own: a key the same file disables, an
     * override outside the closed list, and a sub group neither the file nor the target carries.
     */
    private function refuseAssignment(
        ApplyContext $context,
        ArtifactAssignmentEntry $assignment,
        string $label,
    ): bool {
        $refusals = [];

        if ($context->isKeyDisabled($assignment->getKeyIdentifier())) {
            $refusals[] = sprintf(
                'key "%s" is disabled by the file and assigned by it as well',
                $assignment->getKeyIdentifier()
            );
        }

        foreach (array_keys($assignment->getDefinitionOverride() ?? []) as $property) {
            if (!in_array($property, self::OVERRIDABLE_PROPERTIES, true)) {
                $refusals[] = sprintf('"%s" cannot be overridden per key assignment', $property);
            }
        }

        $subGroup = $assignment->getSubGroup();
        if ($subGroup !== null && $context->resolve(ArtifactSection::SUB_GROUPS, $subGroup) === null) {
            $refusals[] = sprintf('the sub group "%s" exists neither in the file nor in the store', $subGroup);
        }

        foreach ($refusals as $refusal) {
            $context->record(ApplyOutcome::CONFLICT, sprintf('%s: %s', $label, $refusal));
        }

        return $refusals !== [];
    }

    /**
     * A relation whose two endpoints the run could not resolve. A dry run reports it as the
     * creation it would be once the entries it names exist; a real one refuses it, because a
     * relation row cannot be written without both of its ends.
     */
    private function recordUnresolvedEndpoints(ApplyContext $context, string $label): void
    {
        if ($context->isDryRun()) {
            $context->record(ApplyOutcome::CREATED, $label);

            return;
        }

        $context->record(
            ApplyOutcome::CONFLICT,
            sprintf('%s: one of the two entries it names does not exist', $label)
        );
    }

    /**
     * A target row the file does not answer for. It is left in place either way; what is refused is
     * a row whose name the file reuses under another identifier, because applying the file would
     * then have to write two rows under one name.
     *
     * @param array<string, string> $entriesByName
     */
    private function recordUnmatchedTarget(
        ApplyContext $context,
        ArtifactSection $section,
        array $entriesByName,
        SubGroupConfig|CollectionConfig|GroupConfig|KeyConfig $target,
    ): void {
        $identifier = $target->getIdentifier();
        $label = $this->label($section, $target->getName());

        if ($identifier !== null && isset($entriesByName[mb_strtolower($target->getName())])) {
            $context->record(
                ApplyOutcome::CONFLICT,
                sprintf('%s already carries another identifier than the one the file gives it', $label)
            );

            return;
        }

        $context->record(ApplyOutcome::TARGET_ONLY, $label);

        if ($identifier !== null && $target->getId() !== null) {
            // a relation of the file may still name it, so it stays resolvable
            $context->register($section, $identifier, $target->getId());
        }
    }

    /**
     * The identifier first; the name only for a row that carries no identifier of its own, which is
     * the row an identifier is adopted onto.
     *
     * @param array<string, array<string, mixed>> $entries
     * @param array<string, string> $entriesByName
     */
    private function matchEntryKey(
        array $entries,
        array $entriesByName,
        ?string $identifier,
        string $name,
    ): ?string {
        if ($identifier !== null) {
            return isset($entries[$identifier]) ? $identifier : null;
        }

        return $entriesByName[mb_strtolower($name)] ?? null;
    }

    /**
     * The entries of a section by name, lowercased because the name columns are compared case
     * insensitively by the database the rows live in.
     *
     * @param array<string, array<string, mixed>> $entries
     *
     * @return array<string, string>
     */
    private function indexByName(array $entries, string $field): array
    {
        $byName = [];

        foreach ($entries as $entryKey => $entry) {
            $name = $entry[$field] ?? null;

            if (!is_string($name)) {
                continue;
            }

            $byName[mb_strtolower($name)] ??= $entryKey;
        }

        return $byName;
    }

    /**
     * @param Closure(int, int): array<int, SubGroupConfig|CollectionConfig|GroupConfig|KeyConfig> $walk
     *
     * @return Generator<SubGroupConfig|CollectionConfig|GroupConfig|KeyConfig>
     */
    private function walkTargetByIdCursor(ApplyContext $context, Closure $walk): Generator
    {
        if ($context->getStoreId() === null) {
            return;
        }

        $afterId = 0;

        do {
            $targets = $walk($afterId, self::CHUNK_SIZE);

            foreach ($targets as $target) {
                $afterId = (int) $target->getId();

                yield $target;
            }
        } while (count($targets) === self::CHUNK_SIZE);
    }

    /**
     * The relations are walked by offset rather than by cursor: they have no row id, and nothing
     * this step writes touches the columns their ordering is built from.
     *
     * @param Closure(int, int): array<int, ScopedCollectionRelation|ScopedKeyAssignment> $walk
     *
     * @return Generator<ScopedCollectionRelation|ScopedKeyAssignment>
     */
    private function walkTargetByOffset(ApplyContext $context, Closure $walk): Generator
    {
        if ($context->getStoreId() === null) {
            return;
        }

        $offset = 0;

        do {
            $targets = $walk(self::CHUNK_SIZE, $offset);

            foreach ($targets as $target) {
                yield $target;
            }

            $offset += self::CHUNK_SIZE;
        } while (count($targets) === self::CHUNK_SIZE);
    }

    private function describesSameEntity(
        ?string $storedName,
        ?string $storedDescription,
        string $name,
        ?string $description,
    ): bool {
        return $storedName === $name && ($storedDescription ?? '') === ($description ?? '');
    }

    private function describesSameKey(KeyConfig $target, ArtifactKeyEntry $entry): bool
    {
        $storedDefinition = json_decode($target->getDefinition(), true);

        return $target->getName() === $entry->getName()
            && ($target->getTitle() ?? '') === ($entry->getTitle() ?? '')
            && ($target->getDescription() ?? '') === ($entry->getDescription() ?? '')
            && $target->getType() === $entry->getType()
            && $target->getEnabled() === $entry->isEnabled()
            && (is_array($storedDefinition) ? $storedDefinition : null) === $entry->getDefinition();
    }

    /**
     * `sorter` and `mandatory` are always compared; a per assignment column only when the entry
     * supplies it, because an entry that leaves one out does not write it either.
     */
    private function describesSameAssignment(KeyGroupRelation $target, ArtifactAssignmentEntry $entry): bool
    {
        if ($target->getSorter() !== $entry->getSorter() || $target->isMandatory() !== $entry->isMandatory()) {
            return false;
        }

        if ($entry->isMetadataSupplied() && $target->getMetadata() !== $entry->getMetadata()) {
            return false;
        }

        if ($entry->isDefinitionOverrideSupplied()
            && $target->getDefinitionOverride() !== $entry->getDefinitionOverride()) {
            return false;
        }

        return !$entry->isSubGroupSupplied() || $target->getSubGroup() === $entry->getSubGroup();
    }

    /**
     * @param array<string, mixed> $entry
     */
    private function readNullableString(array $entry, string $field): ?string
    {
        $value = $entry[$field] ?? null;

        return is_string($value) ? $value : null;
    }

    private function label(ArtifactSection $section, string $name): string
    {
        return sprintf('%s "%s"', self::SECTION_LEVELS[$section->value], $name);
    }

    private function relationLabel(ArtifactSection $section, string $entryKey): string
    {
        return sprintf(
            '%s "%s"',
            self::SECTION_LEVELS[$section->value],
            implode(' -> ', explode(ArtifactParserInterface::ENTRY_KEY_SEPARATOR, $entryKey))
        );
    }
}
