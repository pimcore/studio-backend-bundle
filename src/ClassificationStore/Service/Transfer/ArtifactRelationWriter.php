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
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Repository\Configuration\KeyRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\SearchIndexFlagResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Util\Constant\ApplyOutcome;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Util\Constant\ArtifactField;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Util\Constant\ArtifactSection;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
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
        private KeyRepositoryInterface $keyRepository,
        private SearchIndexFlagResolverInterface $searchIndexFlagResolver,
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
            $this->keyGroupRelationRepository->createOrUpdateFromArtifact(
                $target === null ? $this->seedSearchIndexFlag($assignment, $keyId) : $assignment,
                $keyId,
                $groupId
            );
        }
    }

    /**
     * The entry of an assignment this apply creates, with the search index flag of its key filled in
     * where the entry leaves the assignment without one - including the explicit `metadata: null` the
     * export always writes for an assignment whose column is NULL.
     *
     * An entry naming the flag keeps what it says and the key is not consulted, an assignment this
     * apply updates is not seeded at all, and a dry run never gets here because it writes nothing.
     *
     * @throws NotFoundException
     */
    private function seedSearchIndexFlag(ArtifactAssignmentEntry $assignment, int $keyId): ArtifactAssignmentEntry
    {
        if ($this->searchIndexFlagResolver->carriesFlag($assignment->getMetadata())) {
            return $assignment;
        }

        $seeded = $this->searchIndexFlagResolver->withKeyDefault(
            $assignment->getMetadata(),
            $this->keyRepository->getById($keyId)->getDefinition()
        );

        return $seeded === null ? $assignment : $assignment->withMetadata($seeded);
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
