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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\ClassificationStore\Repository;

use Codeception\Test\Unit;
use Pimcore\Bundle\StaticResolverBundle\Db\DbResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Repository\CollectionRelationsRepository;
use Pimcore\Model\DataObject\Classificationstore\CollectionGroupRelation;
use function array_map;

/**
 * @internal
 */
final class CollectionRelationsRepositoryTest extends Unit
{
    use ListingDatabaseStubTrait;

    private const int COLLECTION_ID = 7;

    public function testGetFromCollectionOrdersBySorterThenGroupId(): void
    {
        // Equal sorters: without the groupId tie-break the order of these two relations is whatever
        // the storage engine happens to return.
        $this->stubDatabaseReturning([
            $this->createRow(groupId: 4, sorter: 0),
            $this->createRow(groupId: 3, sorter: 0),
        ]);

        $relations = $this->createRepository()->getFromCollection(self::COLLECTION_ID);

        $this->assertStringEndsWith(' ORDER BY `sorter` ASC, `groupId` ASC', $this->getExecutedQuery());
        $this->assertSame(
            [4, 3],
            array_map(static fn (CollectionGroupRelation $relation): int => $relation->getGroupId(), $relations)
        );
    }

    public function testGetFromCollectionFiltersByTheGivenCollection(): void
    {
        $this->stubDatabaseReturning([$this->createRow(groupId: 4, sorter: 0)]);

        $this->createRepository()->getFromCollection(self::COLLECTION_ID);

        $this->assertStringContainsString('colId = ?', $this->getExecutedQuery());
    }

    private function createRepository(): CollectionRelationsRepository
    {
        return new CollectionRelationsRepository($this->makeEmpty(DbResolverInterface::class));
    }

    /**
     * @return array<string, mixed>
     */
    private function createRow(int $groupId, int $sorter): array
    {
        return [
            'colId' => self::COLLECTION_ID,
            'groupId' => $groupId,
            'sorter' => $sorter,
            'name' => 'Group ' . $groupId,
            'description' => '',
        ];
    }
}
