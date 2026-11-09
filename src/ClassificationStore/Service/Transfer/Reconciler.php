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
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Repository\Configuration\CollectionRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Repository\Configuration\CollRelationRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Repository\Configuration\GroupRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Repository\Configuration\KeyGroupRelationRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Repository\Configuration\KeyRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Repository\Configuration\StoreRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Repository\Configuration\SubGroupRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\SearchIndexRemapTriggerInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Util\Constant\ApplyOutcome;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Util\Constant\ArtifactSection;
use Pimcore\Model\DataObject\Classificationstore\CollectionConfig;
use Pimcore\Model\DataObject\Classificationstore\GroupConfig;
use Pimcore\Model\DataObject\Classificationstore\KeyConfig;
use Pimcore\Model\DataObject\Classificationstore\StoreConfig;
use Pimcore\Model\DataObject\Classificationstore\SubGroupConfig;
use Throwable;
use function sprintf;

/**
 * Drives one apply: the seven sections in the order each needs the ones before it resolved, inside
 * one transaction. What an entry describes is the matcher's ({@see ArtifactTargetMatcherInterface}),
 * what one entry becomes is the writer's ({@see ArtifactEntryWriterInterface}), and how a refusal
 * reads is the diagnostics' ({@see ApplyDiagnosticsInterface}).
 *
 * ## Who takes back the search index remap notes
 *
 * The key writes of the sections note the flag changes they make
 * ({@see SearchIndexRemapTriggerInterface}), and a rolled back apply has to take its own notes back
 * with the rows they were about. This class is where that decision belongs, because it is the only
 * one that knows whether a rollback happened at all: it rolls back only the transaction it opened
 * itself, and an apply that joined a transaction a caller owns leaves both the commit and the
 * rollback to that caller - including after a refusal, where it reports the conflicts and writes
 * nothing further.
 *
 * So the notes are taken back here, at each of the two places a rollback is performed, and only
 * back to the mark this apply read before its first section: the notes it found already there
 * belong to writes it did not make - one request reaches the key endpoint and an apply alike - and
 * are about rows this rollback did not touch.
 *
 * @internal
 */
final readonly class Reconciler implements ReconcilerInterface
{
    public function __construct(
        private ArtifactParserInterface $artifactParser,
        private ArtifactTargetMatcherInterface $matcher,
        private ArtifactEntryWriterInterface $writer,
        private ApplyDiagnosticsInterface $diagnostics,
        private CollectionRepositoryInterface $collectionRepository,
        private CollRelationRepositoryInterface $collectionRelationRepository,
        private Connection $connection,
        private GroupRepositoryInterface $groupRepository,
        private KeyGroupRelationRepositoryInterface $keyGroupRelationRepository,
        private KeyRepositoryInterface $keyRepository,
        private SearchIndexRemapTriggerInterface $searchIndexRemapTrigger,
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

        // what this apply's own key writes note from here on, told apart from the notes of whoever
        // wrote before it in the same request: only the former are taken back by a rollback below
        $mark = $this->searchIndexRemapTrigger->mark();

        try {
            $this->applyStore($context, $document, $store);
            $this->holdTheGroupsOfTheStore($context);
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
                // the rows these notes are about are gone with the rollback that just happened,
                // which is the one case a note may be dropped rather than handed over
                $this->searchIndexRemapTrigger->discardSince($mark);
            }

            throw $e;
        }

        $report = $context->getReport();

        if ($ownTransaction) {
            if ($report->hasConflicts()) {
                $this->connection->rollBack();
                $this->searchIndexRemapTrigger->discardSince($mark);
            } else {
                $this->connection->commit();
            }
        }

        return $report;
    }

    /**
     * Takes the group rows of the store before anything this apply writes, because groups before
     * sub groups before relations is the order every writer of this family takes these three tables
     * in ({@see GroupRepositoryInterface::holdGroupsOfStore()} names them). The sections are applied
     * sub groups first - a sub group has to exist before an assignment may tag anything with it -
     * so reaching the group rows only at the group section would hold a sub group while waiting for
     * a group that a concurrent sub group deletion already holds, which is a deadlock neither side
     * retries.
     *
     * It is also what makes the probe a disabled key is refused on exclusive
     * ({@see ArtifactEntryWriterInterface::writeKey()}): the assignments that probe reads hang off
     * these group rows, so an assignment writer either committed before this lock - and is seen by
     * that probe's locking read - or waits until this apply has finished.
     *
     * A dry run writes nothing, holds nothing, and opens no transaction for a lock to live in.
     */
    private function holdTheGroupsOfTheStore(ApplyContext $context): void
    {
        $storeId = $context->getStoreId();

        if ($context->isDryRun() || $storeId === null) {
            return;
        }

        $this->groupRepository->holdGroupsOfStore($storeId);
    }

    private function applyStore(ApplyContext $context, ArtifactDocument $document, ?StoreConfig $store): void
    {
        $identifier = $document->getStoreIdentifier();
        $name = $document->getStoreName();
        $description = $document->getStoreDescription();
        $label = $this->diagnostics->label(ArtifactSection::STORE, $name);

        if ($store === null) {
            // only the console command gets here; the endpoint always has the store of its route
            if ($this->refuseTakenStoreName($context, $name, null, $label)) {
                return;
            }

            $context->record(ApplyOutcome::CREATED, $label);

            if (!$context->isDryRun()) {
                $created = $this->storeRepository->saveFromArtifact(null, $identifier, $name, $description);
                $context->setStoreId($created->getId());
            }

            return;
        }

        $context->setStoreId($store->getId());

        $unchanged = $this->matcher->describesSameEntity(
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

        if ($outcome !== ApplyOutcome::MATCHED_UNCHANGED
            && $this->refuseTakenStoreName($context, $name, $store->getId(), $label)) {
            return;
        }

        $context->record($outcome, $label);

        if ($outcome !== ApplyOutcome::MATCHED_UNCHANGED && !$context->isDryRun()) {
            $this->storeRepository->saveFromArtifact($store, $identifier, $name, $description);
        }
    }

    /**
     * A store name another store already carries, which is the rule
     * {@see StoreRepositoryInterface::create()} and ::update() refuse a write over. Reported as a
     * conflict of the reconcile rather than left to arrive as a failed save, so that a dry run and
     * a command apply name it as well as the HTTP import does.
     */
    private function refuseTakenStoreName(
        ApplyContext $context,
        string $name,
        ?int $exceptId,
        string $label,
    ): bool {
        if (!$this->storeRepository->isNameTaken($name, $exceptId)) {
            return false;
        }

        $context->record(
            ApplyOutcome::CONFLICT,
            sprintf('%s: the name is already another store\'s', $label)
        );

        return true;
    }

    private function applySubGroups(ApplyContext $context, ArtifactDocument $document): void
    {
        $storeId = (int) $context->getStoreId();

        $this->writer->reconcileNamedEntities(
            $context,
            ArtifactSection::SUB_GROUPS,
            $document,
            fn (bool $withIdentifier, int $afterId, int $limit): array => $this->subGroupRepository
                ->getChunkAfterId($storeId, $afterId, $limit, $withIdentifier),
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

        $this->writer->reconcileNamedEntities(
            $context,
            ArtifactSection::COLLECTIONS,
            $document,
            fn (bool $withIdentifier, int $afterId, int $limit): array => $this->collectionRepository
                ->getChunkAfterId($storeId, $afterId, $limit, $withIdentifier),
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

        $this->writer->reconcileNamedEntities(
            $context,
            ArtifactSection::GROUPS,
            $document,
            fn (bool $withIdentifier, int $afterId, int $limit): array => $this->groupRepository
                ->getChunkAfterId($storeId, $afterId, $limit, $withIdentifier),
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

    private function applyKeys(ApplyContext $context, ArtifactDocument $document): void
    {
        $this->writer->reconcileWalkedTargets(
            $context,
            ArtifactSection::KEYS,
            $document->getEntries(ArtifactSection::KEYS),
            $this->keyTargetWalks((int) $context->getStoreId()),
            function (?KeyConfig $target, array $entry) use ($context): void {
                $this->writer->writeKey($context, $target, $entry);
            }
        );
    }

    /**
     * The walks the key rows of the store are taken in: the identifier owners before the rows a name
     * may adopt, which is what {@see ArtifactTargetMatcherInterface::IDENTIFIER_PHASES} is for; and
     * inside each phase the disabled rows first, because a key the file enables moves into the walk
     * that follows, where the entry it has already answered for is skipped.
     *
     * @return list<Closure>
     */
    private function keyTargetWalks(int $storeId): array
    {
        $walks = [];

        foreach (ArtifactTargetMatcherInterface::IDENTIFIER_PHASES as $withIdentifier) {
            foreach ([true, false] as $disabledOnly) {
                $walks[] = fn (int $afterId, int $limit): array => $this->keyRepository
                    ->getChunkAfterId($storeId, $disabledOnly, $afterId, $limit, $withIdentifier);
            }
        }

        return $walks;
    }

    /**
     * The identifier one endpoint of a target relation is named by: the one this run has resolved
     * for the endpoint's row, and only failing that the one the row itself carries.
     *
     * Adoption is why the row alone is not enough. Stamping the file's identifier onto a row that
     * carried none is a write, and a dry run decides it without performing it - so the row still
     * reads NULL while the run has already settled what its identifier is. Keying the relation off
     * the row would then report an assignment the store already carries as target only, and the
     * file's own entry for it as a creation, which is neither the update nor the match it is.
     */
    private function endpointIdentifier(
        ApplyContext $context,
        ArtifactSection $section,
        ?int $id,
        string $carried,
    ): string {
        if ($id === null) {
            return $carried;
        }

        return $context->identifierOf($section, $id) ?? $carried;
    }

    private function applyCollectionRelations(ApplyContext $context, ArtifactDocument $document): void
    {
        $section = ArtifactSection::COLLECTION_GROUP_RELATIONS;
        $storeId = (int) $context->getStoreId();
        $entries = $document->getEntries($section);
        $matched = [];

        $walk = fn (?ScopedCollectionRelation $after, int $limit): array => $this->collectionRelationRepository
            ->getChunkAfter($storeId, $after, $limit);

        foreach ($this->matcher->walkTargetByRowCursor($context, $walk) as $scoped) {
            $relation = $scoped->getRelation();
            $entryKey = $this->endpointIdentifier(
                $context,
                ArtifactSection::COLLECTIONS,
                $relation->getColId(),
                $scoped->getCollectionIdentifier()
            )
                . ArtifactParserInterface::ENTRY_KEY_SEPARATOR
                . $this->endpointIdentifier(
                    $context,
                    ArtifactSection::GROUPS,
                    $relation->getGroupId(),
                    $scoped->getGroupIdentifier()
                );
            $label = $this->diagnostics->relationLabel($section, $entryKey);

            if (!isset($entries[$entryKey])) {
                $context->record(ApplyOutcome::TARGET_ONLY, $label);

                continue;
            }

            $matched[$entryKey] = true;
            $sorter = $this->artifactParser->readSorter($entries[$entryKey], $section);

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

            $this->writer->createCollectionRelation($context, $entryKey, $entry);
        }
    }

    private function applyKeyAssignments(ApplyContext $context, ArtifactDocument $document): void
    {
        $section = ArtifactSection::KEY_GROUP_RELATIONS;
        $storeId = (int) $context->getStoreId();
        $entries = $document->getEntries($section);
        $matched = [];

        $walk = fn (?ScopedKeyAssignment $after, int $limit): array => $this->keyGroupRelationRepository
            ->getChunkAfter($storeId, $after, $limit);

        foreach ($this->matcher->walkTargetByRowCursor($context, $walk) as $scoped) {
            $relation = $scoped->getRelation();
            $entryKey = $this->endpointIdentifier(
                $context,
                ArtifactSection::GROUPS,
                $relation->getGroupId(),
                $scoped->getGroupIdentifier()
            )
                . ArtifactParserInterface::ENTRY_KEY_SEPARATOR
                . $this->endpointIdentifier(
                    $context,
                    ArtifactSection::KEYS,
                    $relation->getKeyId(),
                    $scoped->getKeyIdentifier()
                );

            if (!isset($entries[$entryKey])) {
                $context->record(
                    ApplyOutcome::TARGET_ONLY,
                    $this->diagnostics->relationLabel($section, $entryKey)
                );

                continue;
            }

            $matched[$entryKey] = true;
            $this->writer->writeKeyAssignment($context, $relation, $entries[$entryKey], $entryKey);
        }

        foreach ($entries as $entryKey => $entry) {
            if (isset($matched[$entryKey])) {
                continue;
            }

            $this->writer->writeKeyAssignment($context, null, $entry, $entryKey);
        }
    }
}
