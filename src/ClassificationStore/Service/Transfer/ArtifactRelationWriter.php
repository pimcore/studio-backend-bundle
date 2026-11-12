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
