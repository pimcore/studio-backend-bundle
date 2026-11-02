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
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Repository\Configuration\SubGroupRepository;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\SearchHelperService;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\SearchHelperServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ConflictException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ElementSavingFailedException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\UserNotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Filter\MappedParameter\FilterParameter;
use Pimcore\Bundle\StudioBackendBundle\Listing\Service\ListingFilterInterface;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Translation\Repository\TranslationRepositoryInterface;
use function str_contains;

/**
 * @internal
 */
final class SubGroupRepositoryTest extends Unit
{
    use ListingDatabaseStubTrait;

    private const int STORE_ID = 1;

    private const int SUB_GROUP_ID = 7;

    private const string SUB_GROUP_NAME = 'Dimensions';

    private const string IDENTIFIER = '01JBZ8QF6K3M4N5P6R7S8T9V0W';

    private const string SUB_GROUPS_TABLE = 'classificationstore_subgroups';

    private const string GROUPS_TABLE = 'classificationstore_groups';

    public function testGetListingScopesToTheStoreAndAppliesTheSearchTerm(): void
    {
        $this->stubDatabaseReturning([$this->createSubGroupRow()]);

        $parameters = new FilterParameter(
            columnFilters: [['key' => 'name', 'type' => 'search', 'filterValue' => 'dim']]
        );

        $this->createRepository($this->createSearchHelperService())
            ->getListing($parameters, self::STORE_ID)
            ->load();

        $query = $this->getExecutedQuery();
        $this->assertStringContainsString('`storeId` = :storeId', $query);
        $this->assertStringContainsString('name LIKE ? OR description LIKE ?', $query);
    }

    public function testCreateRefusesADuplicateNameInTheSameStore(): void
    {
        $this->stubDatabaseReturning([$this->createSubGroupRow()]);

        try {
            $this->createRepository()->create(self::SUB_GROUP_NAME, self::STORE_ID, null);
            $this->fail('Expected the duplicate name to be refused.');
        } catch (InvalidArgumentException $e) {
            $this->assertSame(422, $e->getStatusCode());
            $this->assertStringContainsString(self::SUB_GROUP_NAME, $e->getMessage());
            $this->assertStringContainsString('store 1', $e->getMessage());
        }
    }

    public function testRenameRefusesADuplicateNameInTheSameStore(): void
    {
        // the entry being renamed is 8, the name probe answers with 7 - another entry holds the name
        $this->stubDatabaseReturning([$this->createSubGroupRow()]);

        try {
            $this->createRepository()->update(8, self::SUB_GROUP_NAME, null);
            $this->fail('Expected the duplicate name to be refused.');
        } catch (InvalidArgumentException $e) {
            $this->assertSame(422, $e->getStatusCode());
            $this->assertStringContainsString(self::SUB_GROUP_NAME, $e->getMessage());
        }
    }

    /**
     * The name probe answers with the very entry being renamed, which is not a duplicate. There is
     * no database behind the write, so the rename reaches it and fails there - which is what proves
     * the guard let it through, rather than refusing it as a duplicate.
     */
    public function testRenameToItsOwnNameIsAllowed(): void
    {
        $this->stubDatabaseReturning([$this->createSubGroupRow()]);

        $this->expectException(ElementSavingFailedException::class);

        $this->createRepository()->update(self::SUB_GROUP_ID, self::SUB_GROUP_NAME, 'Measured properties');
    }

    public function testDeleteIsRefusedWhileRelationsCarryTheIdentifier(): void
    {
        $this->stubDatabaseAnswering(fn (string $query): array => $this->answerWithGroups($query, ['Technical']));

        try {
            $this->createRepository()->delete(self::SUB_GROUP_ID);
            $this->fail('Expected the deletion to be refused.');
        } catch (ConflictException $e) {
            $this->assertSame(409, $e->getStatusCode());
            $this->assertStringContainsString('Technical', $e->getMessage());
            $this->assertStringContainsString(self::SUB_GROUP_NAME, $e->getMessage());
        }

        // the lookup and the in-use probe, and no statement that could change an assignment
        $this->assertCount(2, $this->getExecutedQueries());
        $this->assertSame([], $this->getExecutedWrites());
    }

    public function testDeleteSucceedsWhenNoRelationCarriesTheIdentifier(): void
    {
        $this->stubDatabaseAnswering(fn (string $query): array => $this->answerWithGroups($query, []));

        $this->createRepository()->delete(self::SUB_GROUP_ID);

        $this->assertCount(2, $this->getExecutedQueries());
        $this->assertContains('DELETE ' . self::SUB_GROUPS_TABLE, $this->getExecutedWrites());
    }

    /**
     * @param list<string> $groupNames
     *
     * @return list<array<string, mixed>>
     */
    private function answerWithGroups(string $query, array $groupNames): array
    {
        if (str_contains($query, self::SUB_GROUPS_TABLE)) {
            return [$this->createSubGroupRow()];
        }

        if (!str_contains($query, self::GROUPS_TABLE)) {
            return [];
        }

        $rows = [];
        foreach ($groupNames as $groupName) {
            $rows[] = ['id' => 3, 'storeId' => self::STORE_ID, 'name' => $groupName, 'description' => ''];
        }

        return $rows;
    }

    private function createRepository(
        ?SearchHelperServiceInterface $searchHelperService = null
    ): SubGroupRepository {
        return new SubGroupRepository(
            $this->makeEmpty(ListingFilterInterface::class),
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
     * @return array<string, mixed>
     */
    private function createSubGroupRow(): array
    {
        return [
            'id' => self::SUB_GROUP_ID,
            'storeId' => self::STORE_ID,
            'name' => self::SUB_GROUP_NAME,
            'description' => 'Measured properties',
            'identifier' => self::IDENTIFIER,
            'creationDate' => 1734567890,
            'modificationDate' => 1734567890,
        ];
    }
}
