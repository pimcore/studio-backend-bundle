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
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Repository\Configuration\CollRelationRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Repository\Configuration\KeyGroupRelationRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Repository\Configuration\KeyRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Util\Constant\ApplyOutcome;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Util\Constant\ArtifactField;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Util\Constant\ArtifactSection;
use Pimcore\Model\DataObject\Classificationstore\CollectionConfig;
use Pimcore\Model\DataObject\Classificationstore\GroupConfig;
use Pimcore\Model\DataObject\Classificationstore\KeyConfig;
use Pimcore\Model\DataObject\Classificationstore\KeyGroupRelation;
use Pimcore\Model\DataObject\Classificationstore\SubGroupConfig;

/**
 * @internal
 */
final readonly class ArtifactEntryWriter implements ArtifactEntryWriterInterface
{
    public function __construct(
        private ArtifactParserInterface $artifactParser,
        private ArtifactTargetMatcherInterface $matcher,
        private ApplyDiagnosticsInterface $diagnostics,
        private ApplyRefusalsInterface $refusals,
        private CollRelationRepositoryInterface $collectionRelationRepository,
        private KeyGroupRelationRepositoryInterface $keyGroupRelationRepository,
        private KeyRepositoryInterface $keyRepository,
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function reconcileNamedEntities(
        ApplyContext $context,
        ArtifactSection $section,
        ArtifactDocument $document,
        Closure $walk,
        Closure $save,
    ): void {
        $walks = [];

        // the rows that own an identifier before the rows a name may adopt, which is what
        // ArtifactTargetMatcherInterface::IDENTIFIER_PHASES is for
        foreach (ArtifactTargetMatcherInterface::IDENTIFIER_PHASES as $withIdentifier) {
            $walks[] = static fn (int $afterId, int $limit): array => $walk($withIdentifier, $afterId, $limit);
        }

        $this->reconcileWalkedTargets(
            $context,
            $section,
            $document->getEntries($section),
            $walks,
            function (
                SubGroupConfig|CollectionConfig|GroupConfig|null $target,
                array $entry,
            ) use ($context, $section, $save): void {
                $this->writeNamedEntity($context, $section, $target, $entry, $save);
            }
        );
    }

    /**
     * {@inheritdoc}
     */
    public function reconcileWalkedTargets(
        ApplyContext $context,
        ArtifactSection $section,
        array $entries,
        array $walks,
        Closure $write,
    ): void {
        $entriesByName = $this->matcher->indexByName($entries, ArtifactField::NAME->value);
        $matched = [];
        $awaitingTheLevel = [];

        $reportUnmatched = function (
            SubGroupConfig|CollectionConfig|GroupConfig|KeyConfig $target,
        ) use ($context, $section, $entriesByName, &$matched, &$awaitingTheLevel): void {
            if ($this->diagnostics->unmatchedTargetAwaitsTheLevel($section, $entriesByName, $target)) {
                $awaitingTheLevel[] = $target;

                return;
            }

            $this->diagnostics->recordUnmatchedTarget($context, $section, $entriesByName, $target, $matched);
        };

        foreach ($walks as $walk) {
            foreach ($this->matcher->walkTargetByIdCursor($context, $walk) as $target) {
                $entryKey = $this->claimEntryKey($entries, $entriesByName, $target, $matched, $reportUnmatched);

                if ($entryKey !== null) {
                    $write($target, $entries[$entryKey]);
                }
            }
        }

        // now that every walk of the level has claimed what it claims: whether the entry one of
        // these rows merely shares a name with is answered for is a fact about the level, so it is
        // read once the ledger is whole rather than at the row the walk happened to reach first
        foreach ($awaitingTheLevel as $target) {
            $this->diagnostics->recordUnmatchedTarget($context, $section, $entriesByName, $target, $matched);
        }

        foreach ($entries as $entryKey => $entry) {
            if (!isset($matched[$entryKey])) {
                $write(null, $entry);
            }
        }
    }

    /**
     * The entry one walked row claims, or null where it claims nothing: a row whose entry is already
     * another row's, and a row the section describes nothing of, are both left in place as the
     * target they are - the first because the file is then naming one row under two identifiers,
     * the second because an apply never deletes.
     *
     * A row reached twice is the one case that is neither, and is silent: stamping an identifier
     * onto an adopted row moves it into the walk that follows, where the entry it has already
     * answered for would otherwise read as claimed by somebody else.
     *
     * `$matched` is the claim ledger of the level, and the claim is recorded in it. A row that
     * claims nothing and is not the same row twice is handed to `$reportUnmatched`, which decides
     * whether the level can answer for it yet.
     *
     * @param array<string, array<string, mixed>> $entries
     * @param array<string, string> $entriesByName
     * @param array<string, int> $matched
     * @param Closure(SubGroupConfig|CollectionConfig|GroupConfig|KeyConfig): void $reportUnmatched
     */
    private function claimEntryKey(
        array $entries,
        array $entriesByName,
        SubGroupConfig|CollectionConfig|GroupConfig|KeyConfig $target,
        array &$matched,
        Closure $reportUnmatched,
    ): ?string {
        $entryKey = $this->matcher->matchEntryKey(
            $entries,
            $entriesByName,
            $target->getIdentifier(),
            $target->getName()
        );
        $claimedBy = $entryKey === null ? null : ($matched[$entryKey] ?? null);

        if ($claimedBy !== null && $claimedBy === (int) $target->getId()) {
            // the same row reached twice, in a walk its own write moved it into
            return null;
        }

        if ($entryKey === null || $claimedBy !== null) {
            $reportUnmatched($target);

            return null;
        }

        $matched[$entryKey] = (int) $target->getId();

        return $entryKey;
    }

    /**
     * {@inheritdoc}
     */
    public function writeKey(ApplyContext $context, ?KeyConfig $target, array $entry): void
    {
        $keyEntry = $this->artifactParser->readKeyEntry($entry);
        $label = $this->diagnostics->label(ArtifactSection::KEYS, $keyEntry->getName());

        // the file declares it, which is what a reference to it from a later section asks about
        $context->plan(ArtifactSection::KEYS, $keyEntry->getIdentifier());

        if (!$keyEntry->isEnabled()) {
            $context->markKeyDisabled($keyEntry->getIdentifier());
        }

        $outcome = match (true) {
            $target === null => ApplyOutcome::CREATED,
            $target->getIdentifier() === null => ApplyOutcome::ADOPTED,
            $this->matcher->describesSameKey($target, $keyEntry) => ApplyOutcome::MATCHED_UNCHANGED,
            default => ApplyOutcome::UPDATED,
        };

        if ($outcome !== ApplyOutcome::MATCHED_UNCHANGED
            && $target !== null
            && !$keyEntry->isEnabled()
            && $this->refusals->refuseDisablingAnAssignedKey($context, $target, $keyEntry->getName())) {
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
     * {@inheritdoc}
     */
    public function createCollectionRelation(ApplyContext $context, string $entryKey, array $entry): void
    {
        $section = ArtifactSection::COLLECTION_GROUP_RELATIONS;
        $label = $this->diagnostics->relationLabel($section, $entryKey);
        $collectionIdentifier = (string) $entry[ArtifactField::COLLECTION->value];
        $groupIdentifier = (string) $entry[ArtifactField::GROUP->value];
        $collectionId = $context->resolve(ArtifactSection::COLLECTIONS, $collectionIdentifier);
        $groupId = $context->resolve(ArtifactSection::GROUPS, $groupIdentifier);

        if ($collectionId === null || $groupId === null) {
            $this->diagnostics->recordUnresolvedEndpoints(
                $context,
                $label,
                $this->missingEndpoints(
                    $context,
                    ArtifactSection::COLLECTIONS,
                    $collectionIdentifier,
                    ArtifactSection::GROUPS,
                    $groupIdentifier
                )
            );

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

    /**
     * {@inheritdoc}
     */
    public function writeKeyAssignment(
        ApplyContext $context,
        ?KeyGroupRelation $target,
        array $entry,
        string $entryKey,
    ): void {
        $section = ArtifactSection::KEY_GROUP_RELATIONS;
        $assignment = $this->artifactParser->readAssignmentEntry($entry);
        $label = $this->diagnostics->relationLabel($section, $entryKey);

        if ($this->refusals->refuseAssignment($context, $assignment, $label)) {
            return;
        }

        $groupId = $target?->getGroupId()
            ?? $context->resolve(ArtifactSection::GROUPS, $assignment->getGroupIdentifier());
        $keyId = $target?->getKeyId()
            ?? $context->resolve(ArtifactSection::KEYS, $assignment->getKeyIdentifier());

        if ($groupId === null || $keyId === null) {
            $this->diagnostics->recordUnresolvedEndpoints(
                $context,
                $label,
                $this->missingEndpoints(
                    $context,
                    ArtifactSection::GROUPS,
                    $assignment->getGroupIdentifier(),
                    ArtifactSection::KEYS,
                    $assignment->getKeyIdentifier()
                )
            );

            return;
        }

        if ($target !== null && $this->matcher->describesSameAssignment($target, $assignment)) {
            $context->record(ApplyOutcome::MATCHED_UNCHANGED, $label);

            return;
        }

        $context->record($target === null ? ApplyOutcome::CREATED : ApplyOutcome::UPDATED, $label);

        if (!$context->isDryRun()) {
            $this->keyGroupRelationRepository->createOrUpdateFromArtifact($assignment, $keyId, $groupId);
        }
    }

    /**
     * @param array<string, mixed> $entry
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
        $description = $this->matcher->readNullableString($entry, ArtifactField::DESCRIPTION->value);
        $label = $this->diagnostics->label($section, $name);

        // the file declares it, which is what a reference to it from a later section asks about
        $context->plan($section, $identifier);

        $unchanged = $target !== null && $this->matcher->describesSameEntity(
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

        if ($this->refusals->refuseTakenName($context, $section, $outcome, $name, $target?->getId(), $label)) {
            return;
        }

        $context->record($outcome, $label);

        $id = $target?->getId();

        if ($outcome !== ApplyOutcome::MATCHED_UNCHANGED && !$context->isDryRun()) {
            $id = $save($target, $identifier, $name, $description);
        }

        if ($id !== null) {
            $context->register($section, $identifier, $id);
        }
    }

    /**
     * The endpoints of a relation that exist in neither the file nor the store, named the way the
     * file names them. An endpoint the file declares is left out: in a dry run it has no row and so
     * no id, and that is not the same thing as not existing.
     */
    private function missingEndpoints(
        ApplyContext $context,
        ArtifactSection $firstSection,
        string $first,
        ArtifactSection $secondSection,
        string $second,
    ): array {
        $missing = [];

        if (!$context->isKnown($firstSection, $first)) {
            $missing[] = $first;
        }

        if (!$context->isKnown($secondSection, $second)) {
            $missing[] = $second;
        }

        return $missing;
    }
}
