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
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Repository\Configuration\KeyRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\SearchIndexRemapTriggerInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Util\Constant\ApplyOutcome;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Util\Constant\ArtifactField;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Util\Constant\ArtifactSection;
use Pimcore\Model\DataObject\Classificationstore\CollectionConfig;
use Pimcore\Model\DataObject\Classificationstore\GroupConfig;
use Pimcore\Model\DataObject\Classificationstore\KeyConfig;
use Pimcore\Model\DataObject\Classificationstore\KeyGroupRelation;
use Pimcore\Model\DataObject\Classificationstore\SubGroupConfig;
use function array_key_exists;

/**
 * Writes the entity sections itself - sub groups, collections, groups and keys - and composes the
 * relation writer ({@see ArtifactRelationWriterInterface}) for the two join sections, which are
 * written from a resolved pair of endpoint identifiers rather than from a walked row of one level
 * and are the only writes that read a key.
 *
 * @internal
 */
final readonly class ArtifactEntryWriter implements ArtifactEntryWriterInterface
{
    public function __construct(
        private ArtifactParserInterface $artifactParser,
        private ArtifactTargetMatcherInterface $matcher,
        private ApplyDiagnosticsInterface $diagnostics,
        private ApplyRefusalsInterface $refusals,
        private ArtifactRelationWriterInterface $relationWriter,
        private KeyRepositoryInterface $keyRepository,
        private SearchIndexRemapTriggerInterface $searchIndexRemapTrigger,
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

        $reportUnmatched = function (
            SubGroupConfig|CollectionConfig|GroupConfig|KeyConfig $target,
        ) use ($context, $section): void {
            $this->diagnostics->recordUnmatchedTarget($context, $section, $target);
        };

        foreach ($walks as $walk) {
            foreach ($this->matcher->walkTargetByIdCursor($context, $walk) as $target) {
                $entryKey = $this->claimEntryKey($entries, $entriesByName, $target, $matched, $reportUnmatched);

                if ($entryKey !== null) {
                    $write($target, $entries[$entryKey]);
                }
            }
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
            $write = $this->keyRepository->saveFromArtifact($target, (int) $context->getStoreId(), $keyEntry);
            $saved = $write->getConfig();
            $id = $saved->getId();

            // a key the file creates is in no mapping yet and holds no value, so it changes no index
            if ($outcome !== ApplyOutcome::CREATED) {
                // the definition the row carried when the write took its lock, never the one this
                // instance held before the apply read the rest of its sections: an apply runs long
                // enough for a key endpoint to have committed over it in between, and comparing
                // against the stale blob would drop that writer's flag change
                $this->searchIndexRemapTrigger->noteFlagChange(
                    $saved->getStoreId(),
                    $write->getDefinitionBefore(),
                    $saved->getDefinition()
                );
            }
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
        $this->relationWriter->createCollectionRelation($context, $entryKey, $entry);
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
        $this->relationWriter->writeKeyAssignment($context, $target, $entry, $entryKey);
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
        $customMetadataSupplied = array_key_exists(ArtifactField::CUSTOM_METADATA->value, $entry);
        $customMetadata = $entry[ArtifactField::CUSTOM_METADATA->value] ?? null;
        $label = $this->diagnostics->label($section, $name);

        // the file declares it, which is what a reference to it from a later section asks about
        $context->plan($section, $identifier);

        $unchanged = $target !== null && $this->matcher->describesSameEntity(
            $target->getName(),
            $target->getDescription(),
            $name,
            $description,
            $target->getCustomMetadata(),
            $customMetadataSupplied,
            $customMetadata
        );

        $outcome = match (true) {
            $target === null => ApplyOutcome::CREATED,
            $target->getIdentifier() === null => ApplyOutcome::ADOPTED,
            $unchanged => ApplyOutcome::MATCHED_UNCHANGED,
            default => ApplyOutcome::UPDATED,
        };

        if ($this->refusals->refuseTakenName(
            $context,
            $section,
            $outcome,
            $name,
            $target?->getId(),
            $label,
            $identifier
        )) {
            return;
        }

        $context->record($outcome, $label);

        $id = $target?->getId();

        if ($outcome !== ApplyOutcome::MATCHED_UNCHANGED && !$context->isDryRun()) {
            $id = $save(
                $target,
                $identifier,
                $name,
                $description,
                $customMetadataSupplied,
                $customMetadata
            );
        }

        if ($id !== null) {
            $context->register($section, $identifier, $id);
        }
    }
}
