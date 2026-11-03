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
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Repository\Configuration\CollectionRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Repository\Configuration\CollRelationRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Repository\Configuration\GroupRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Repository\Configuration\KeyGroupRelationRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Repository\Configuration\KeyRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Repository\Configuration\SubGroupRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Util\Constant\ApplyOutcome;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Util\Constant\ArtifactField;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Util\Constant\ArtifactSection;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\DatabaseException;
use Pimcore\Model\DataObject\Classificationstore\CollectionConfig;
use Pimcore\Model\DataObject\Classificationstore\GroupConfig;
use Pimcore\Model\DataObject\Classificationstore\KeyConfig;
use Pimcore\Model\DataObject\Classificationstore\KeyGroupRelation;
use Pimcore\Model\DataObject\Classificationstore\SubGroupConfig;
use function array_keys;
use function implode;
use function in_array;
use function sprintf;

/**
 * @internal
 */
final readonly class ArtifactEntryWriter implements ArtifactEntryWriterInterface
{
    /**
     * What a `definitionOverride` may carry, mirroring the closed list the model enforces
     * ({@see KeyGroupRelation::setDefinitionOverride()}). It is restated here because a reconcile
     * has to name every property it refuses rather than stop at the first one the model throws on.
     */
    private const array OVERRIDABLE_PROPERTIES = ['options', 'defaultUnit', 'validUnits'];

    /**
     * Whether the level's own write path refuses a name another row of the store already carries on
     * a rename as well as on a creation. The rule is the entity's, not the artifact's: a sub group
     * is refused both ({@see SubGroupRepositoryInterface::create()}, ::update()), a collection and
     * a group a creation only (their ::create() probes the name, their ::update() does not), and a
     * key neither - key names are not unique per store, and a guard on them was withdrawn in the
     * requirements register because it would make a call that succeeds today start failing.
     */
    private const array NAME_GUARDED_ON_RENAME = [
        ArtifactSection::SUB_GROUPS->value => true,
        ArtifactSection::COLLECTIONS->value => false,
        ArtifactSection::GROUPS->value => false,
    ];

    public function __construct(
        private ArtifactParserInterface $artifactParser,
        private ArtifactTargetMatcherInterface $matcher,
        private ApplyDiagnosticsInterface $diagnostics,
        private CollectionRepositoryInterface $collectionRepository,
        private CollRelationRepositoryInterface $collectionRelationRepository,
        private GroupRepositoryInterface $groupRepository,
        private KeyGroupRelationRepositoryInterface $keyGroupRelationRepository,
        private KeyRepositoryInterface $keyRepository,
        private SubGroupRepositoryInterface $subGroupRepository,
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
        $entries = $document->getEntries($section);
        $entriesByName = $this->matcher->indexByName($entries, ArtifactField::NAME->value);
        $matched = [];

        foreach ($this->matcher->walkTargetByIdCursor($context, $walk) as $target) {
            $entryKey = $this->matcher->matchEntryKey(
                $entries,
                $entriesByName,
                $target->getIdentifier(),
                $target->getName()
            );

            if ($entryKey === null) {
                $this->diagnostics->recordUnmatchedTarget($context, $section, $entriesByName, $target);

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

        if ($this->refuseAssignment($context, $assignment, $label)) {
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

        if ($this->refuseTakenName($context, $section, $outcome, $name, $target?->getId(), $label)) {
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
     * A name the level's own write path would refuse, which an artifact may not be written past
     * either: a file naming two identifiers the same way, or one creating a name a row the file
     * says nothing about already carries, would otherwise persist the duplicate the API refuses.
     *
     * Reported as a conflict of the reconcile rather than thrown, so that a dry run names it too
     * and a real run names every other refusal beside it.
     *
     * @throws DatabaseException
     */
    private function refuseTakenName(
        ApplyContext $context,
        ArtifactSection $section,
        ApplyOutcome $outcome,
        string $name,
        ?int $exceptId,
        string $label,
    ): bool {
        if ($outcome === ApplyOutcome::MATCHED_UNCHANGED) {
            return false;
        }

        if ($outcome !== ApplyOutcome::CREATED && (self::NAME_GUARDED_ON_RENAME[$section->value] ?? false) !== true) {
            return false;
        }

        // the store answers for the rows that are there; the run itself for the ones it is writing
        $claimed = $context->claimName($section, $name);

        if ($claimed && !$this->nameIsTaken($section, (int) $context->getStoreId(), $name, $exceptId)) {
            return false;
        }

        $context->record(
            ApplyOutcome::CONFLICT,
            sprintf('%s: the name is already another entry\'s in this store', $label)
        );

        return true;
    }

    /**
     * @throws DatabaseException
     */
    private function nameIsTaken(ArtifactSection $section, int $storeId, string $name, ?int $exceptId): bool
    {
        return match ($section) {
            ArtifactSection::SUB_GROUPS => $this->subGroupRepository->isNameTaken($storeId, $name, $exceptId),
            ArtifactSection::COLLECTIONS => $this->collectionRepository->isNameTaken($storeId, $name),
            ArtifactSection::GROUPS => $this->groupRepository->isNameTaken($storeId, $name),
            default => false,
        };
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
        if ($subGroup !== null && !$context->isKnown(ArtifactSection::SUB_GROUPS, $subGroup)) {
            $refusals[] = sprintf('the sub group "%s" exists neither in the file nor in the store', $subGroup);
        }

        foreach ($refusals as $refusal) {
            $context->record(ApplyOutcome::CONFLICT, sprintf('%s: %s', $label, $refusal));
        }

        return $refusals !== [];
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
