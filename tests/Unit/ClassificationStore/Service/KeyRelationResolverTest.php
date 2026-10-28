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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\ClassificationStore\Service;

use Codeception\Test\Unit;
use Exception;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Repository\KeyGroupRelationRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\KeyRelationResolver;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\DatabaseException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Model\DataObject\Classificationstore\KeyGroupRelation;
use Symfony\Contracts\Service\ResetInterface;
use function array_keys;

/**
 * @internal
 */
final class KeyRelationResolverTest extends Unit
{
    private const int GROUP_ID = 7;

    private const int OTHER_GROUP_ID = 8;

    private const int THIRD_GROUP_ID = 9;

    private const int KEY_ID = 42;

    private const int ABSENT_KEY_ID = 99;

    /**
     * The sorter ascending, then id ascending order is the `ORDER BY` of
     * {@see \Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Repository\KeyGroupRelationRepository}; the row id
     * is not on the model, so what the resolver owes is to hand the loaded rows back in the order it got them.
     * The relations here are keyed 13, 42, 5 so that keying by keyId cannot be mistaken for sorting by it.
     *
     * @throws Exception
     */
    public function testGetByGroupKeysByKeyIdWithoutReorderingTheLoadedRelations(): void
    {
        $resolver = new KeyRelationResolver($this->repositoryReturning([
            $this->createRelation(13, self::GROUP_ID),
            $this->createRelation(self::KEY_ID, self::GROUP_ID),
            $this->createRelation(5, self::GROUP_ID),
        ]));

        $relations = $resolver->getByGroup(self::GROUP_ID);

        $this->assertSame([13, self::KEY_ID, 5], array_keys($relations));
        $this->assertSame(self::KEY_ID, $relations[self::KEY_ID]->getKeyId());
    }

    /**
     * @throws Exception
     */
    public function testGetByGroupAndKeyThrowsForAnAbsentPair(): void
    {
        $resolver = new KeyRelationResolver($this->repositoryReturning([
            $this->createRelation(self::KEY_ID, self::GROUP_ID),
        ]));

        $this->expectException(NotFoundException::class);

        $resolver->getByGroupAndKey(self::GROUP_ID, self::ABSENT_KEY_ID);
    }

    /**
     * @throws Exception
     */
    public function testRepeatedReadsOfTheSameGroupIssueOneLoad(): void
    {
        $resolver = $this->resolverLoadingOnce();
        $resolver->getByGroup(self::GROUP_ID);
        $resolver->getByGroup(self::GROUP_ID);

        $resolver = $this->resolverLoadingOnce();
        $resolver->getByGroup(self::GROUP_ID);
        $resolver->getByGroupAndKey(self::GROUP_ID, self::KEY_ID);

        $resolver = $this->resolverLoadingOnce();
        $resolver->getByGroup(self::GROUP_ID);
        $resolver->getByGroups([self::GROUP_ID]);
    }

    /**
     * @throws Exception
     */
    public function testGetByGroupsLoadsOnlyTheGroupsNotMemoisedYet(): void
    {
        $loads = [];
        $repository = $this->createMock(KeyGroupRelationRepositoryInterface::class);
        $repository
            ->method('getByGroupIds')
            ->willReturnCallback(function (array $groupIds) use (&$loads): array {
                $loads[] = $groupIds;

                return [$this->createRelation(self::KEY_ID, $groupIds[0])];
            });

        $resolver = new KeyRelationResolver($repository);
        $resolver->getByGroup(self::GROUP_ID);

        $relations = $resolver->getByGroups([self::GROUP_ID, self::OTHER_GROUP_ID, self::THIRD_GROUP_ID]);

        $this->assertSame([[self::GROUP_ID], [self::OTHER_GROUP_ID, self::THIRD_GROUP_ID]], $loads);
        $this->assertSame(
            [self::GROUP_ID, self::OTHER_GROUP_ID, self::THIRD_GROUP_ID],
            array_keys($relations)
        );
        $this->assertSame([], $relations[self::THIRD_GROUP_ID]);
    }

    /**
     * @throws Exception
     */
    public function testReturnedRelationsAreCopies(): void
    {
        $resolver = $this->resolverLoadingOnce();

        $relation = $resolver->getByGroup(self::GROUP_ID)[self::KEY_ID];
        $relation->setSorter(99);
        $relation->setMandatory(true);

        $this->assertSame(0, $resolver->getByGroup(self::GROUP_ID)[self::KEY_ID]->getSorter());
        $this->assertFalse($resolver->getByGroups([self::GROUP_ID])[self::GROUP_ID][self::KEY_ID]->isMandatory());

        $single = $resolver->getByGroupAndKey(self::GROUP_ID, self::KEY_ID);
        $single->setSorter(99);
        $single->setMandatory(true);

        $this->assertSame(0, $resolver->getByGroupAndKey(self::GROUP_ID, self::KEY_ID)->getSorter());
        $this->assertFalse($resolver->getByGroupAndKey(self::GROUP_ID, self::KEY_ID)->isMandatory());
    }

    /**
     * @throws Exception
     */
    public function testReadAfterClearSeesNewRelations(): void
    {
        $repository = $this->createMock(KeyGroupRelationRepositoryInterface::class);
        $repository
            ->expects($this->exactly(2))
            ->method('getByGroupIds')
            ->willReturnOnConsecutiveCalls(
                [$this->createRelation(self::KEY_ID, self::GROUP_ID)],
                [
                    $this->createRelation(self::KEY_ID, self::GROUP_ID),
                    $this->createRelation(self::ABSENT_KEY_ID, self::GROUP_ID),
                ]
            );

        $resolver = new KeyRelationResolver($repository);

        $this->assertCount(1, $resolver->getByGroup(self::GROUP_ID));

        $resolver->clear();

        $this->assertCount(2, $resolver->getByGroup(self::GROUP_ID));
    }

    /**
     * @throws Exception
     */
    public function testAFailedLoadMemoisesNothing(): void
    {
        $repository = $this->createMock(KeyGroupRelationRepositoryInterface::class);
        $repository
            ->method('getByGroupIds')
            ->willReturnOnConsecutiveCalls(
                $this->throwException(new DatabaseException()),
                [$this->createRelation(self::KEY_ID, self::GROUP_ID)]
            );

        $resolver = new KeyRelationResolver($repository);

        try {
            $resolver->getByGroup(self::GROUP_ID);
        } catch (DatabaseException) {
            // the next read has to try again instead of being served the empty memo
        }

        $this->assertCount(1, $resolver->getByGroup(self::GROUP_ID));
    }

    /**
     * @throws Exception
     */
    public function testResetBehavesAsClear(): void
    {
        $repository = $this->createMock(KeyGroupRelationRepositoryInterface::class);
        $repository
            ->expects($this->exactly(2))
            ->method('getByGroupIds')
            ->willReturn([$this->createRelation(self::KEY_ID, self::GROUP_ID)]);

        $resolver = new KeyRelationResolver($repository);

        $this->assertInstanceOf(ResetInterface::class, $resolver);

        $resolver->getByGroup(self::GROUP_ID);
        $resolver->reset();
        $resolver->getByGroup(self::GROUP_ID);
    }

    /**
     * @throws Exception
     */
    private function resolverLoadingOnce(): KeyRelationResolver
    {
        $repository = $this->createMock(KeyGroupRelationRepositoryInterface::class);
        $repository
            ->expects($this->once())
            ->method('getByGroupIds')
            ->with([self::GROUP_ID])
            ->willReturn([$this->createRelation(self::KEY_ID, self::GROUP_ID)]);

        return new KeyRelationResolver($repository);
    }

    /**
     * @param KeyGroupRelation[] $relations
     *
     * @throws Exception
     */
    private function repositoryReturning(array $relations): KeyGroupRelationRepositoryInterface
    {
        $repository = $this->createMock(KeyGroupRelationRepositoryInterface::class);
        $repository
            ->method('getByGroupIds')
            ->willReturn($relations);

        return $repository;
    }

    private function createRelation(int $keyId, int $groupId): KeyGroupRelation
    {
        $relation = new KeyGroupRelation();
        $relation->setKeyId($keyId);
        $relation->setGroupId($groupId);
        $relation->setName('key-' . $keyId);

        return $relation;
    }
}
