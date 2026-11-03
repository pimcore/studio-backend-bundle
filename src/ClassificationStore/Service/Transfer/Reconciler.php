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
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Util\Constant\ApplyOutcome;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Util\Constant\ArtifactField;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Util\Constant\ArtifactSection;
use Pimcore\Model\DataObject\Classificationstore\CollectionConfig;
use Pimcore\Model\DataObject\Classificationstore\GroupConfig;
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

        $this->writer->reconcileNamedEntities(
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

        $this->writer->reconcileNamedEntities(
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

    private function applyKeys(ApplyContext $context, ArtifactDocument $document): void
    {
        $storeId = (int) $context->getStoreId();
        $entries = $document->getEntries(ArtifactSection::KEYS);
        $entriesByName = $this->matcher->indexByName($entries, ArtifactField::NAME->value);
        $matched = [];

        // the disabled rows first: a key the file enables moves into the walk that follows, where
        // the entry it has already answered for is skipped
        foreach ([true, false] as $disabledOnly) {
            $walk = fn (int $afterId, int $limit): array => $this->keyRepository
                ->getChunkAfterId($storeId, $disabledOnly, $afterId, $limit);

            foreach ($this->matcher->walkTargetByIdCursor($context, $walk) as $target) {
                $entryKey = $this->matcher->matchEntryKey(
                    $entries,
                    $entriesByName,
                    $target->getIdentifier(),
                    $target->getName()
                );

                if ($entryKey === null) {
                    $this->diagnostics->recordUnmatchedTarget(
                        $context,
                        ArtifactSection::KEYS,
                        $entriesByName,
                        $target
                    );

                    continue;
                }

                if (isset($matched[$entryKey])) {
                    continue;
                }

                $matched[$entryKey] = true;
                $this->writer->writeKey($context, $target, $entries[$entryKey]);
            }
        }

        foreach ($entries as $entryKey => $entry) {
            if (isset($matched[$entryKey])) {
                continue;
            }

            $this->writer->writeKey($context, null, $entry);
        }
    }

    private function applyCollectionRelations(ApplyContext $context, ArtifactDocument $document): void
    {
        $section = ArtifactSection::COLLECTION_GROUP_RELATIONS;
        $storeId = (int) $context->getStoreId();
        $entries = $document->getEntries($section);
        $matched = [];

        $walk = fn (int $limit, int $offset): array => $this->collectionRelationRepository
            ->getChunkByStore($storeId, $limit, $offset);

        foreach ($this->matcher->walkTargetByOffset($context, $walk) as $scoped) {
            $entryKey = $scoped->getCollectionIdentifier()
                . ArtifactParserInterface::ENTRY_KEY_SEPARATOR
                . $scoped->getGroupIdentifier();
            $label = $this->diagnostics->relationLabel($section, $entryKey);

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

            $this->writer->createCollectionRelation($context, $entryKey, $entry);
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

        foreach ($this->matcher->walkTargetByOffset($context, $walk) as $scoped) {
            $entryKey = $scoped->getGroupIdentifier()
                . ArtifactParserInterface::ENTRY_KEY_SEPARATOR
                . $scoped->getKeyIdentifier();

            if (!isset($entries[$entryKey])) {
                $context->record(
                    ApplyOutcome::TARGET_ONLY,
                    $this->diagnostics->relationLabel($section, $entryKey)
                );

                continue;
            }

            $matched[$entryKey] = true;
            $this->writer->writeKeyAssignment($context, $scoped->getRelation(), $entries[$entryKey], $entryKey);
        }

        foreach ($entries as $entryKey => $entry) {
            if (isset($matched[$entryKey])) {
                continue;
            }

            $this->writer->writeKeyAssignment($context, null, $entry, $entryKey);
        }
    }
}
