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
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Repository\GroupConfigRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Repository\KeyGroupRelationRepository;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\SearchHelperService;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\SearchHelperServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\UserNotFoundException;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\CollectionParameters;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Translation\Repository\TranslationRepositoryInterface;
use Pimcore\Model\DataObject\Classificationstore\GroupConfig;
use Pimcore\Model\DataObject\Classificationstore\KeyGroupRelation;
use function array_map;

/**
 * @internal
 */
final class KeyGroupRelationRepositoryTest extends Unit
{
    use ListingDatabaseStubTrait;

    private const int STORE_ID = 1;

    private const int GROUP_ID = 7;

    public function testGetByGroupIdOrdersBySorterThenId(): void
    {
        // Equal sorters: without the id tie-break the order of these two keys is whatever the
        // storage engine happens to return.
        $this->stubDatabaseReturning([
            $this->createRow(keyId: 13, sorter: 0),
            $this->createRow(keyId: 11, sorter: 0),
        ]);

        $relations = $this->createRepository()->getByGroupId(self::GROUP_ID);

        $this->assertStringEndsWith(' ORDER BY `sorter` ASC, `id` ASC', $this->getExecutedQuery());
        $this->assertSame([13, 11], $this->getKeyIds($relations));
    }

    public function testGetPaginatedKeyGroupRelationByStoreOrdersByGroupSorterThenId(): void
    {
        $this->stubDatabaseReturning([
            $this->createRow(keyId: 13, sorter: 0),
            $this->createRow(keyId: 11, sorter: 0),
        ]);

        $relations = $this->createRepository()->getPaginatedKeyGroupRelationByStore(
            self::STORE_ID,
            new CollectionParameters(page: 2, pageSize: 2)
        );

        $query = $this->getExecutedQuery();
        $this->assertStringContainsString(' ORDER BY `groupId` ASC, `sorter` ASC, `id` ASC', $query);
        $this->assertStringEndsWith(' LIMIT 2,2', $query);
        $this->assertSame([13, 11], $this->getKeyIds($relations));
    }

    public function testGetPaginatedKeyGroupRelationByStoreKeepsItsOrderWithASearchTerm(): void
    {
        $this->stubDatabaseReturning([$this->createRow(keyId: 11, sorter: 0)]);

        $this->createRepository($this->createSearchHelperService())->getPaginatedKeyGroupRelationByStore(
            self::STORE_ID,
            new CollectionParameters(page: 1, pageSize: 10),
            searchTerm: 'weight'
        );

        $query = $this->getExecutedQuery();
        $this->assertStringContainsString("name LIKE '%weight%'", $query);
        $this->assertStringContainsString(' ORDER BY `groupId` ASC, `sorter` ASC, `id` ASC', $query);
    }

    private function createRepository(
        ?SearchHelperServiceInterface $searchHelperService = null
    ): KeyGroupRelationRepository {
        $groupConfig = (new GroupConfig())->setId(self::GROUP_ID);

        return new KeyGroupRelationRepository(
            $this->makeEmpty(GroupConfigRepositoryInterface::class, ['getAllGroupsByStore' => [$groupConfig]]),
            $searchHelperService ?? $this->makeEmpty(SearchHelperServiceInterface::class)
        );
    }

    private function createSearchHelperService(): SearchHelperService
    {
        return new SearchHelperService(
            $this->makeEmpty(TranslationRepositoryInterface::class),
            $this->makeEmpty(SecurityServiceInterface::class, [
                'getCurrentUser' => static fn (): never => throw new UserNotFoundException(),
            ])
        );
    }

    /**
     * @param KeyGroupRelation[] $relations
     *
     * @return int[]
     */
    private function getKeyIds(array $relations): array
    {
        return array_map(static fn (KeyGroupRelation $relation): int => $relation->getKeyId(), $relations);
    }

    /**
     * @return array<string, mixed>
     */
    private function createRow(int $keyId, int $sorter): array
    {
        return [
            'keyId' => $keyId,
            'groupId' => self::GROUP_ID,
            'sorter' => $sorter,
            'name' => 'key' . $keyId,
            'description' => '',
            'type' => 'input',
            'definition' => '{"fieldtype":"input"}',
            'enabled' => 1,
            'mandatory' => 0,
        ];
    }
}
