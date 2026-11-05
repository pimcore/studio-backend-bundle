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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\ClassificationStore\Service\Transfer;

use Codeception\Test\Unit;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Repository\Configuration\CollectionRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Repository\Configuration\GroupRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Repository\Configuration\KeyGroupRelationRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Repository\Configuration\SubGroupRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\Transfer\ApplyContext;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\Transfer\ApplyRefusals;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\Transfer\ApplyReport;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\Transfer\ArtifactAssignmentEntry;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Util\Constant\ApplyOutcome;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Util\Constant\ArtifactSection;
use Pimcore\Model\DataObject\Classificationstore\KeyConfig;

/**
 * What an apply refuses to write, and why - the three refusals the writer asks about before it
 * writes anything: a name the level's own API would refuse, a key the file disables while the store
 * still assigns it, and the refusals one key assignment carries on its own.
 *
 * @internal
 */
final class ApplyRefusalsTest extends Unit
{
    private const int STORE_ID = 1;

    /**
     * A sub group's own ::update() refuses a name another row of the store carries, so a rename the
     * file asks for is refused here rather than arriving as a failed save.
     */
    public function testASubGroupRenameOntoANameTheStoreCarriesIsRefused(): void
    {
        $context = $this->createContext();
        $refusals = $this->createRefusals(subGroupNameTaken: true);

        $refused = $refusals->refuseTakenName(
            $context,
            ArtifactSection::SUB_GROUPS,
            ApplyOutcome::UPDATED,
            'Dimensions',
            7,
            'sub group "Dimensions"'
        );

        $this->assertTrue($refused);
        $this->assertSame(
            ['sub group "Dimensions": the name is already another entry\'s in this store'],
            $context->getReport()->getNamed(ApplyOutcome::CONFLICT)
        );
    }

    /**
     * A collection's and a group's ::update() do not probe the name, so a rename at those levels is
     * written - and the store is not even asked.
     */
    public function testARenameIsNotRefusedAtTheLevelsWhoseUpdateDoesNotProbeTheName(): void
    {
        foreach ([ArtifactSection::COLLECTIONS, ArtifactSection::GROUPS] as $section) {
            $context = $this->createContext();
            $refusals = $this->createRefusals(collectionNameTaken: true, groupNameTaken: true);

            $this->assertFalse(
                $refusals->refuseTakenName($context, $section, ApplyOutcome::UPDATED, 'Alpha', 7, 'label'),
                $section->value
            );
            $this->assertSame(0, $context->getReport()->getCount(ApplyOutcome::CONFLICT));
        }
    }

    /**
     * A creation is refused at every guarded level, because every one of them probes the name on
     * ::create().
     */
    public function testACreationOntoANameTheStoreCarriesIsRefusedAtEveryGuardedLevel(): void
    {
        $sections = [
            ArtifactSection::SUB_GROUPS,
            ArtifactSection::COLLECTIONS,
            ArtifactSection::GROUPS,
        ];

        foreach ($sections as $section) {
            $context = $this->createContext();
            $refusals = $this->createRefusals(
                collectionNameTaken: true,
                groupNameTaken: true,
                subGroupNameTaken: true
            );

            $this->assertTrue(
                $refusals->refuseTakenName($context, $section, ApplyOutcome::CREATED, 'Alpha', null, 'label'),
                $section->value
            );
        }
    }

    /**
     * An entry the row it was matched to already says is nothing the guard has to ask about.
     */
    public function testAnUnchangedEntryIsNeverRefused(): void
    {
        $context = $this->createContext();
        $refusals = $this->createRefusals(subGroupNameTaken: true);

        $this->assertFalse(
            $refusals->refuseTakenName(
                $context,
                ArtifactSection::SUB_GROUPS,
                ApplyOutcome::MATCHED_UNCHANGED,
                'Dimensions',
                7,
                'label'
            )
        );
    }

    /**
     * The run answers for the names it is writing itself: a file naming one name under two
     * identifiers is refused on the second, which the store cannot know about yet.
     */
    public function testTheSecondEntryOfTheFileClaimingOneNameIsRefused(): void
    {
        $context = $this->createContext();
        $refusals = $this->createRefusals();

        $this->assertFalse(
            $refusals->refuseTakenName($context, ArtifactSection::GROUPS, ApplyOutcome::CREATED, 'Alpha', null, 'one')
        );
        $this->assertTrue(
            $refusals->refuseTakenName($context, ArtifactSection::GROUPS, ApplyOutcome::CREATED, 'alpha', null, 'two')
        );
        $this->assertSame(
            ['two: the name is already another entry\'s in this store'],
            $context->getReport()->getNamed(ApplyOutcome::CONFLICT)
        );
    }

    /**
     * Disabling a key deletes every assignment of it, so a file that disables one the store still
     * assigns is refused, naming the groups that would have been untagged.
     */
    public function testDisablingAKeyTheStoreStillAssignsIsRefusedNamingTheGroups(): void
    {
        $context = $this->createContext();
        $refusals = $this->createRefusals(assignedGroupNames: ['Alpha', 'Beta']);

        $refused = $refusals->refuseDisablingAnAssignedKey($context, $this->createKey(), 'weight');

        $this->assertTrue($refused);
        $this->assertSame(
            ['key "weight" is disabled by the file but still assigned to the group(s) "Alpha", "Beta"'],
            $context->getReport()->getNamed(ApplyOutcome::CONFLICT)
        );
    }

    public function testDisablingAnUnassignedKeyIsNotRefused(): void
    {
        $context = $this->createContext();
        $refusals = $this->createRefusals();

        $this->assertFalse($refusals->refuseDisablingAnAssignedKey($context, $this->createKey(), 'weight'));
        $this->assertSame(0, $context->getReport()->getCount(ApplyOutcome::CONFLICT));
    }

    /**
     * A real run writes under the probe's answer, so the probe is a locking read; a dry run writes
     * nothing, holds nothing and asks without the lock.
     */
    public function testTheProbeLocksInARealRunAndNotInADryRun(): void
    {
        foreach ([false, true] as $dryRun) {
            $holds = [];
            $keyGroupRelationRepository = $this->createMock(KeyGroupRelationRepositoryInterface::class);
            $keyGroupRelationRepository
                ->method('getGroupNamesByKeyId')
                ->willReturnCallback(static function (int $keyId, bool $hold = false) use (&$holds): array {
                    $holds[] = $hold;

                    return [];
                });

            $refusals = new ApplyRefusals(
                $this->makeEmpty(CollectionRepositoryInterface::class),
                $this->makeEmpty(GroupRepositoryInterface::class),
                $keyGroupRelationRepository,
                $this->makeEmpty(SubGroupRepositoryInterface::class)
            );

            $refusals->refuseDisablingAnAssignedKey($this->createContext($dryRun), $this->createKey(), 'weight');

            $this->assertSame([!$dryRun], $holds, $dryRun ? 'dry run' : 'real run');
        }
    }

    public function testAnAssignmentOfAKeyTheSameFileDisablesIsRefused(): void
    {
        $context = $this->createContext();
        $context->markKeyDisabled('K1');
        $refusals = $this->createRefusals();

        $refused = $refusals->refuseAssignment($context, $this->createAssignment(), 'key assignment "G1 -> K1"');

        $this->assertTrue($refused);
        $this->assertSame(
            ['key assignment "G1 -> K1": key "K1" is disabled by the file and assigned by it as well'],
            $context->getReport()->getNamed(ApplyOutcome::CONFLICT)
        );
    }

    /**
     * Every property outside the closed list is named, rather than only the first one the model
     * would have thrown on.
     */
    public function testAnOverrideOutsideTheClosedListIsRefusedNamingEveryProperty(): void
    {
        $context = $this->createContext();
        $refusals = $this->createRefusals();

        $refused = $refusals->refuseAssignment(
            $context,
            $this->createAssignment(definitionOverride: [
                'options' => [['key' => 'a', 'value' => 'a']],
                'type' => 'input',
                'name' => 'weight',
            ]),
            'label'
        );

        $this->assertTrue($refused);
        $this->assertSame(
            [
                'label: "type" cannot be overridden per key assignment',
                'label: "name" cannot be overridden per key assignment',
            ],
            $context->getReport()->getNamed(ApplyOutcome::CONFLICT)
        );
    }

    /**
     * The search index flag is not overridable per assignment: the per assignment expression of it
     * is the `metadata` column, so a `definitionOverride` naming it is outside the closed list like
     * anything else.
     */
    public function testAnOverrideNamingTheSearchIndexFlagIsRefused(): void
    {
        $context = $this->createContext();
        $refusals = $this->createRefusals();

        $refused = $refusals->refuseAssignment(
            $context,
            $this->createAssignment(definitionOverride: ['indexed' => false]),
            'label'
        );

        $this->assertTrue($refused);
        $this->assertSame(
            ['label: "indexed" cannot be overridden per key assignment'],
            $context->getReport()->getNamed(ApplyOutcome::CONFLICT)
        );
    }

    public function testASubGroupNeitherTheFileNorTheStoreCarriesIsRefused(): void
    {
        $context = $this->createContext();
        $refusals = $this->createRefusals();

        $refused = $refusals->refuseAssignment($context, $this->createAssignment(subGroup: 'SG1'), 'label');

        $this->assertTrue($refused);
        $this->assertSame(
            ['label: the sub group "SG1" exists neither in the file nor in the store'],
            $context->getReport()->getNamed(ApplyOutcome::CONFLICT)
        );
    }

    public function testASubGroupTheRunKnowsAboutIsNotRefused(): void
    {
        $context = $this->createContext();
        $context->plan(ArtifactSection::SUB_GROUPS, 'SG1');
        $refusals = $this->createRefusals();

        $this->assertFalse(
            $refusals->refuseAssignment($context, $this->createAssignment(subGroup: 'SG1'), 'label')
        );
        $this->assertSame(0, $context->getReport()->getCount(ApplyOutcome::CONFLICT));
    }

    /**
     * @param array<string, mixed>|null $definitionOverride
     */
    private function createAssignment(
        ?array $definitionOverride = null,
        ?string $subGroup = null,
    ): ArtifactAssignmentEntry {
        return new ArtifactAssignmentEntry(
            'G1',
            'K1',
            0,
            false,
            definitionOverride: $definitionOverride,
            subGroup: $subGroup
        );
    }

    private function createKey(): KeyConfig
    {
        $key = new KeyConfig();
        $key->setId(7);
        $key->setStoreId(self::STORE_ID);

        return $key;
    }

    private function createContext(bool $dryRun = false): ApplyContext
    {
        $context = new ApplyContext($dryRun, new ApplyReport());
        $context->setStoreId(self::STORE_ID);

        return $context;
    }

    /**
     * @param list<string> $assignedGroupNames
     */
    private function createRefusals(
        bool $collectionNameTaken = false,
        bool $groupNameTaken = false,
        bool $subGroupNameTaken = false,
        array $assignedGroupNames = [],
    ): ApplyRefusals {
        $collectionRepository = $this->createMock(CollectionRepositoryInterface::class);
        $collectionRepository->method('isNameTaken')->willReturn($collectionNameTaken);

        $groupRepository = $this->createMock(GroupRepositoryInterface::class);
        $groupRepository->method('isNameTaken')->willReturn($groupNameTaken);

        $subGroupRepository = $this->createMock(SubGroupRepositoryInterface::class);
        $subGroupRepository->method('isNameTaken')->willReturn($subGroupNameTaken);

        $keyGroupRelationRepository = $this->createMock(KeyGroupRelationRepositoryInterface::class);
        $keyGroupRelationRepository->method('getGroupNamesByKeyId')->willReturn($assignedGroupNames);

        return new ApplyRefusals(
            $collectionRepository,
            $groupRepository,
            $keyGroupRelationRepository,
            $subGroupRepository
        );
    }
}
