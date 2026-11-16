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
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Repository\Configuration\CollRelationRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Repository\Configuration\GroupRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Repository\Configuration\KeyGroupRelationRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Repository\Configuration\KeyRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Repository\Configuration\SubGroupRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\Transfer\ApplyContext;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\Transfer\ApplyDiagnostics;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\Transfer\ApplyReport;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\Transfer\ScopedCollectionRelation;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\Transfer\ScopedKeyAssignment;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\Transfer\StoreMemberPurger;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Util\Constant\ApplyOutcome;
use Pimcore\Model\DataObject\Classificationstore\CollectionConfig;
use Pimcore\Model\DataObject\Classificationstore\CollectionGroupRelation;
use Pimcore\Model\DataObject\Classificationstore\GroupConfig;
use Pimcore\Model\DataObject\Classificationstore\KeyConfig;
use Pimcore\Model\DataObject\Classificationstore\KeyGroupRelation;
use Pimcore\Model\DataObject\Classificationstore\SubGroupConfig;
use function sprintf;

/**
 * What a replace takes out of the target, and what a dry run says about it before it happens: the
 * report is the whole of what a caller has to warn with, so it is asserted as a list rather than
 * only for its length.
 *
 * @internal
 */
final class StoreMemberPurgerTest extends Unit
{
    private const int STORE_ID = 1;

    private const int GROUP_ID = 41;

    private const int KEY_ID = 51;

    private const int COLLECTION_ID = 31;

    private const int SUB_GROUP_ID = 21;

    private const string GROUP_NAME = 'Measurements';

    private const string KEY_NAME = 'weight';

    private const string READ_CHUNK = 'getChunkAfterId';

    private const string DELETE = 'delete';

    /**
     * @var list<string>
     */
    private array $deletes = [];

    /**
     * The entities before the relations, each level in the words the rest of the apply reports it
     * in. A caller shows the first of these, so the order is part of what it is given.
     */
    public function testEveryRowIsReportedEntitiesBeforeRelations(): void
    {
        $context = $this->createContext(false);

        $this->createPurger()->purge($context);

        $this->assertSame(
            [
                'sub group "Physical"',
                'collection "Cables"',
                'group "Measurements"',
                'key "weight"',
                'collection group relation "01COLLECTION -> 01GROUP"',
                'key assignment "01GROUP -> 01KEY"',
            ],
            $context->getReport()->getNamed(ApplyOutcome::REMOVED)
        );
        $this->assertSame(6, $context->getReport()->getCount(ApplyOutcome::REMOVED));
    }

    /**
     * Keys and groups first - the database takes both relation tables with them - and sub groups
     * last, because their delete is refused while an assignment still tags one.
     */
    public function testTheEntitiesAreDeletedKeysAndGroupsBeforeCollectionsAndSubGroups(): void
    {
        $this->createPurger()->purge($this->createContext(false));

        $this->assertSame(
            [
                sprintf('key:%d', self::KEY_ID),
                sprintf('group:%d', self::GROUP_ID),
                sprintf('collection:%d', self::COLLECTION_ID),
                sprintf('subGroup:%d', self::SUB_GROUP_ID),
            ],
            $this->deletes
        );
    }

    /**
     * A dry run answers with the same report and deletes none of it, which is what a warning is
     * shown from.
     */
    public function testADryRunReportsTheSameAndDeletesNothing(): void
    {
        $dry = $this->createContext(true);
        $real = $this->createContext(false);

        $this->createPurger()->purge($dry);
        $this->deletes = [];
        $this->createPurger()->purge($real);

        $this->assertSame(
            $real->getReport()->getNamed(ApplyOutcome::REMOVED),
            $dry->getReport()->getNamed(ApplyOutcome::REMOVED)
        );
        $this->assertNotSame([], $this->deletes);
    }

    /**
     * An apply that has no store to write into removes nothing, rather than reading store 0.
     */
    public function testAnApplyWithoutAStoreRemovesNothing(): void
    {
        $context = new ApplyContext(false, new ApplyReport());

        $this->createPurger()->purge($context);

        $this->assertSame(0, $context->getReport()->getCount(ApplyOutcome::REMOVED));
        $this->assertSame([], $this->deletes);
    }

    private function createContext(bool $dryRun): ApplyContext
    {
        $context = new ApplyContext($dryRun, new ApplyReport());
        $context->setStoreId(self::STORE_ID);

        return $context;
    }

    private function createPurger(): StoreMemberPurger
    {
        $this->deletes = [];

        return new StoreMemberPurger(
            new ApplyDiagnostics(),
            $this->makeEmpty(CollectionRepositoryInterface::class, [
                self::READ_CHUNK => [$this->createCollection()],
                self::DELETE => fn (int $id) => $this->recordDelete('collection', $id),
            ]),
            $this->makeEmpty(CollRelationRepositoryInterface::class, [
                'getChunkAfter' => [$this->createCollectionRelation()],
            ]),
            $this->makeEmpty(GroupRepositoryInterface::class, [
                self::READ_CHUNK => [$this->createGroup()],
                self::DELETE => fn (int $id) => $this->recordDelete('group', $id),
            ]),
            $this->makeEmpty(KeyGroupRelationRepositoryInterface::class, [
                'getChunkAfter' => [$this->createKeyAssignment()],
            ]),
            $this->makeEmpty(KeyRepositoryInterface::class, [
                // the enabled walk carries the one key; the disabled walk carries none
                self::READ_CHUNK => fn (int $storeId, bool $disabledOnly): array => $disabledOnly
                    ? []
                    : [$this->createKey()],
                self::DELETE => fn (int $id) => $this->recordDelete('key', $id),
            ]),
            $this->makeEmpty(SubGroupRepositoryInterface::class, [
                self::READ_CHUNK => [$this->createSubGroup()],
                self::DELETE => fn (int $id) => $this->recordDelete('subGroup', $id),
            ])
        );
    }

    private function recordDelete(string $level, int $id): void
    {
        $this->deletes[] = sprintf('%s:%d', $level, $id);
    }

    private function createSubGroup(): SubGroupConfig
    {
        $config = new SubGroupConfig();
        $config->setId(self::SUB_GROUP_ID);
        $config->setName('Physical');

        return $config;
    }

    private function createCollection(): CollectionConfig
    {
        $config = new CollectionConfig();
        $config->setId(self::COLLECTION_ID);
        $config->setName('Cables');

        return $config;
    }

    private function createGroup(): GroupConfig
    {
        $config = new GroupConfig();
        $config->setId(self::GROUP_ID);
        $config->setName(self::GROUP_NAME);

        return $config;
    }

    private function createKey(): KeyConfig
    {
        $config = new KeyConfig();
        $config->setId(self::KEY_ID);
        $config->setName(self::KEY_NAME);
        $config->setEnabled(true);

        return $config;
    }

    private function createCollectionRelation(): ScopedCollectionRelation
    {
        $relation = new CollectionGroupRelation();
        $relation->setColId(self::COLLECTION_ID);
        $relation->setGroupId(self::GROUP_ID);

        return new ScopedCollectionRelation($relation, '01COLLECTION', '01GROUP');
    }

    private function createKeyAssignment(): ScopedKeyAssignment
    {
        $relation = new KeyGroupRelation();
        $relation->setGroupId(self::GROUP_ID);
        $relation->setKeyId(self::KEY_ID);

        return new ScopedKeyAssignment($relation, '01GROUP', '01KEY');
    }
}
