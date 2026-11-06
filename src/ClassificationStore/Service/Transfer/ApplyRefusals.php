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

use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Repository\Configuration\CollectionRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Repository\Configuration\GroupRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Repository\Configuration\KeyGroupRelationRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Repository\Configuration\SubGroupRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\SearchIndexFlagResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Util\Constant\ApplyOutcome;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Util\Constant\ArtifactSection;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\DatabaseException;
use Pimcore\Model\DataObject\Classificationstore\KeyConfig;
use Pimcore\Model\DataObject\Classificationstore\KeyGroupRelation;
use function array_keys;
use function implode;
use function in_array;
use function sprintf;

/**
 * @internal
 */
final readonly class ApplyRefusals implements ApplyRefusalsInterface
{
    /**
     * What a `definitionOverride` may carry, mirroring the closed list the model enforces
     * ({@see KeyGroupRelation::setDefinitionOverride()}). It is restated here because a reconcile
     * has to name every property it refuses rather than stop at the first one the model throws on.
     *
     * The reserved `indexed` member is deliberately not one of them: the per assignment expression
     * of that flag is the `metadata` column ({@see SearchIndexFlagResolverInterface}).
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
        private CollectionRepositoryInterface $collectionRepository,
        private GroupRepositoryInterface $groupRepository,
        private KeyGroupRelationRepositoryInterface $keyGroupRelationRepository,
        private SubGroupRepositoryInterface $subGroupRepository,
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function refuseTakenName(
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
     * Writing `enabled = 0` deletes every assignment of the key
     * ({@see \Pimcore\Model\DataObject\Classificationstore\KeyConfig\Dao::update()}), so a file
     * that disables a key the target still assigns would untag groups it says nothing about.
     * Refused, naming those groups.
     *
     * A real run reads the assignments under a lock rather than from its own snapshot, and then
     * writes under that answer: an assignment committed after the apply's transaction began would
     * otherwise be invisible to the probe and deleted by the disable a moment later, which is the
     * one loss the refusal exists to prevent. The locking read is exclusive against the assignment
     * writers because the apply holds the store's group rows first
     * ({@see GroupRepositoryInterface::holdGroupsOfStore()}). A dry run writes nothing and asks
     * without the lock.
     */
    public function refuseDisablingAnAssignedKey(ApplyContext $context, KeyConfig $target, string $name): bool
    {
        $groups = $this->keyGroupRelationRepository->getGroupNamesByKeyId(
            (int) $target->getId(),
            !$context->isDryRun()
        );

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
     * {@inheritdoc}
     */
    public function refuseAssignment(
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
}
