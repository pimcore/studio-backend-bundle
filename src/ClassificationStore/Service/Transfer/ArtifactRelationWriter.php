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

use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Repository\Configuration\CollRelationRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Repository\Configuration\KeyGroupRelationRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Util\Constant\ApplyOutcome;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Util\Constant\ArtifactField;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Util\Constant\ArtifactSection;
use Pimcore\Model\DataObject\Classificationstore\KeyGroupRelation;

/**
 * @internal
 */
final readonly class ArtifactRelationWriter implements ArtifactRelationWriterInterface
{
    public function __construct(
        private ArtifactParserInterface $artifactParser,
        private ArtifactTargetMatcherInterface $matcher,
        private ApplyDiagnosticsInterface $diagnostics,
        private ApplyRefusalsInterface $refusals,
        private CollRelationRepositoryInterface $collectionRelationRepository,
        private KeyGroupRelationRepositoryInterface $keyGroupRelationRepository,
    ) {
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
        $skipped = $this->skippedEndpoints(
            $context,
            [[ArtifactSection::COLLECTIONS, $collectionIdentifier], [ArtifactSection::GROUPS, $groupIdentifier]]
        );

        if ($skipped !== []) {
            $this->diagnostics->recordSkippedEndpoints($context, $label, $skipped);

            return;
        }

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

        $skipped = $this->skippedEndpoints($context, [
            [ArtifactSection::GROUPS, $assignment->getGroupIdentifier()],
            [ArtifactSection::KEYS, $assignment->getKeyIdentifier()],
            [ArtifactSection::SUB_GROUPS, $assignment->getSubGroup()],
        ]);

        if ($skipped !== []) {
            $this->diagnostics->recordSkippedEndpoints($context, $label, $skipped);

            return;
        }

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
     * {@inheritdoc}
     */
    public function reconcileCollectionRelations(ApplyContext $context, ArtifactDocument $document): void
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

            $this->createCollectionRelation($context, $entryKey, $entry);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function reconcileKeyAssignments(ApplyContext $context, ArtifactDocument $document): void
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
            $this->writeKeyAssignment($context, $relation, $entries[$entryKey], $entryKey);
        }

        foreach ($entries as $entryKey => $entry) {
            if (isset($matched[$entryKey])) {
                continue;
            }

            $this->writeKeyAssignment($context, null, $entry, $entryKey);
        }
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

    /**
     * The endpoints of a relation this run skipped, named the way the file names them.
     *
     * @param list<array{0: ArtifactSection, 1: ?string}> $endpoints
     *
     * @return list<string>
     */
    private function skippedEndpoints(ApplyContext $context, array $endpoints): array
    {
        $skipped = [];

        foreach ($endpoints as [$section, $identifier]) {
            if ($identifier !== null && $context->isSkipped($section, $identifier)) {
                $skipped[] = $identifier;
            }
        }

        return $skipped;
    }

    /**
     * The endpoints of a relation that exist in neither the file nor the store, named the way the
     * file names them. An endpoint the file declares is left out: in a dry run it has no row and so
     * no id, and that is not the same thing as not existing.
     *
     * @return list<string>
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
