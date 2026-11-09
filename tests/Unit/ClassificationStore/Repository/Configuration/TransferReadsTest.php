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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\ClassificationStore\Repository\Configuration;

use Codeception\Test\Unit;
use Doctrine\DBAL\Connection;
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\ClassificationStore\GroupConfigResolverInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\ClassificationStore\KeyConfigResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Repository\Configuration\CollRelationRepository;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Repository\Configuration\GroupRepository;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Repository\Configuration\KeyGroupRelationRepository;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Repository\Configuration\KeyRepository;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\SearchHelperServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\Transfer\ArtifactEntryMapperInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\Transfer\ScopedCollectionRelation;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\Transfer\ScopedKeyAssignment;
use Pimcore\Bundle\StudioBackendBundle\Listing\Service\ListingFilterInterface;
use Pimcore\Bundle\StudioBackendBundle\Tests\Unit\ClassificationStore\Repository\ListingDatabaseStubTrait;
use Pimcore\Bundle\StudioBackendBundle\Tests\Unit\ClassificationStore\Service\InMemoryKeyLocalizationResolver;
use Pimcore\Model\DataObject\Classificationstore\CollectionGroupRelation;
use Pimcore\Model\DataObject\Classificationstore\KeyGroupRelation;
use function str_contains;

/**
 * The reads a configuration transfer takes, asserted on the statements that reach the database -
 * which is where paging and locking are observable at all, there being no database in this suite.
 *
 * Three rules are at stake. An export pages by a cursor on the identifier it sorts by, never by an
 * offset into it: a configuration write committing between two pages shifts the rows an offset has
 * not reached yet, and the shifted row is then left out of a download that reports success. An
 * apply walks the rows that carry an identifier before the rows that do not, so that the row owning
 * an entry's identifier claims it before an identifierless namesake can be adopted onto the wrong
 * row. And a decision an apply is about to write under is read as of now and held, rather than from
 * a snapshot that may predate a writer which has already committed.
 *
 * @internal
 */
final class TransferReadsTest extends Unit
{
    use ListingDatabaseStubTrait;

    private const int STORE_ID = 1;

    private const int CHUNK_SIZE = 200;

    private const string IDENTIFIER_CURSOR = "(COALESCE(`identifier`, ''), `id`) > "
        . '(:afterIdentifier, :afterIdentifierCursorId)';

    public function testTheExportWalkPagesByTheIdentifierCursorAndNotByAnOffset(): void
    {
        $repository = $this->createKeyRepository();

        $repository->getChunkAfterIdentifier(self::STORE_ID, false, '01JA', 7, self::CHUNK_SIZE);

        $query = $this->getExecutedQuery();

        $this->assertTrue(str_contains($query, self::IDENTIFIER_CURSOR), $query);
        $this->assertTrue(str_contains($query, 'ORDER BY `identifier` ASC'), $query);
        $this->assertFalse(str_contains($query, 'OFFSET'), $query);
    }

    /**
     * The first page has nothing to be taken after, so it carries no cursor condition at all.
     */
    public function testTheFirstExportPageCarriesNoCursorCondition(): void
    {
        $repository = $this->createKeyRepository();

        $repository->getChunkAfterIdentifier(self::STORE_ID, false, null, 0, self::CHUNK_SIZE);

        $query = $this->getExecutedQuery();

        $this->assertFalse(str_contains($query, 'COALESCE'), $query);
        $this->assertTrue(str_contains($query, 'ORDER BY `identifier` ASC'), $query);
    }

    /**
     * @dataProvider identifierPhases
     *
     * @param bool $withIdentifier the identifier phase the walk covers
     * @param string $condition what narrows the walk to it
     */
    public function testTheApplyWalkNarrowsToOneIdentifierPhase(bool $withIdentifier, string $condition): void
    {
        $repository = $this->createKeyRepository();

        $repository->getChunkAfterId(self::STORE_ID, false, 7, self::CHUNK_SIZE, $withIdentifier);

        $query = $this->getExecutedQuery();

        $this->assertTrue(str_contains($query, $condition), $query);
        $this->assertTrue(str_contains($query, 'ORDER BY `id` ASC'), $query);
    }

    /**
     * @return array<string, array{bool, string}>
     */
    public static function identifierPhases(): array
    {
        return [
            'the rows that own an identifier' => [true, '`identifier` IS NOT NULL'],
            'the rows a name may adopt' => [false, '`identifier` IS NULL'],
        ];
    }

    /**
     * No phase is every row in one walk, which is what a caller that does not match by name wants.
     */
    public function testAWalkWithoutAPhaseNarrowsNothing(): void
    {
        $repository = $this->createKeyRepository();

        $repository->getChunkAfterId(self::STORE_ID, false, 7, self::CHUNK_SIZE);

        $query = $this->getExecutedQuery();

        $this->assertFalse(str_contains($query, '`identifier` IS'), $query);
    }

    /**
     * A relation has no single row id, so its cursor is the identifier pair it is ordered by with
     * its whole primary key behind it - total even for an endpoint that carries no identifier yet.
     */
    public function testTheKeyAssignmentWalkIsTakenAfterTheIdentifierPairAndThePrimaryKey(): void
    {
        $captured = null;
        $parameters = null;
        $connection = $this->makeEmpty(Connection::class, [
            'fetchAllAssociative' => static function (string $query, array $params) use (
                &$captured,
                &$parameters
            ): array {
                $captured = $query;
                $parameters = $params;

                return [];
            },
        ]);

        $repository = new KeyGroupRelationRepository(
            $this->makeEmpty(ListingFilterInterface::class),
            $connection,
            $this->makeEmpty(ArtifactEntryMapperInterface::class)
        );

        $relation = new KeyGroupRelation();
        $relation->setGroupId(41);
        $relation->setKeyId(51);

        $repository->getChunkAfter(
            self::STORE_ID,
            new ScopedKeyAssignment($relation, '01GROUP', '01KEY'),
            self::CHUNK_SIZE
        );

        $this->assertTrue(
            str_contains(
                (string) $captured,
                "(COALESCE(`g`.`identifier`, ''), COALESCE(`k`.`identifier`, ''),"
                . ' `r`.`groupId`, `r`.`keyId`) > (?, ?, ?, ?)'
            ),
            (string) $captured
        );
        $this->assertFalse(str_contains((string) $captured, 'OFFSET'), (string) $captured);
        $this->assertSame([self::STORE_ID, '01GROUP', '01KEY', 41, 51, self::CHUNK_SIZE], $parameters);
    }

    public function testTheCollectionRelationWalkIsTakenAfterTheIdentifierPairAndThePrimaryKey(): void
    {
        $captured = null;
        $parameters = null;
        $connection = $this->makeEmpty(Connection::class, [
            'fetchAllAssociative' => static function (string $query, array $params) use (
                &$captured,
                &$parameters
            ): array {
                $captured = $query;
                $parameters = $params;

                return [];
            },
        ]);

        $repository = new CollRelationRepository($this->makeEmpty(ListingFilterInterface::class), $connection);

        $relation = new CollectionGroupRelation();
        $relation->setColId(31);
        $relation->setGroupId(41);

        $repository->getChunkAfter(
            self::STORE_ID,
            new ScopedCollectionRelation($relation, '01COLLECTION', '01GROUP'),
            self::CHUNK_SIZE
        );

        $this->assertTrue(
            str_contains(
                (string) $captured,
                "(COALESCE(`c`.`identifier`, ''), COALESCE(`g`.`identifier`, ''),"
                . ' `r`.`colId`, `r`.`groupId`) > (?, ?, ?, ?)'
            ),
            (string) $captured
        );
        $this->assertFalse(str_contains((string) $captured, 'OFFSET'), (string) $captured);
        $this->assertSame([self::STORE_ID, '01COLLECTION', '01GROUP', 31, 41, self::CHUNK_SIZE], $parameters);
    }

    /**
     * The lock an apply fronts its writes with: the groups of the store, held, ascending by id -
     * which is the order every writer of this family takes these tables in.
     */
    public function testTheGroupHoldIsALockingReadOrderedByTheRowId(): void
    {
        $captured = null;
        $connection = $this->makeEmpty(Connection::class, [
            'fetchFirstColumn' => static function (string $query) use (&$captured): array {
                $captured = $query;

                return [];
            },
        ]);

        $repository = new GroupRepository(
            $this->makeEmpty(GroupConfigResolverInterface::class),
            $this->makeEmpty(ListingFilterInterface::class),
            $this->makeEmpty(SearchHelperServiceInterface::class),
            $connection
        );

        $repository->holdGroupsOfStore(self::STORE_ID);

        $this->assertTrue(str_contains((string) $captured, 'FOR UPDATE'), (string) $captured);
        $this->assertTrue(str_contains((string) $captured, 'ORDER BY `id`'), (string) $captured);
        $this->assertTrue(
            str_contains((string) $captured, '`classificationstore_groups` WHERE `storeId` = ?'),
            (string) $captured
        );
    }

    /**
     * The probe a disable is refused over is held when the caller is about to write under it: a
     * plain read answers from a view that can predate an assignment a competing writer has already
     * committed, and the disable would then drop the very row the refusal exists to protect.
     *
     * @dataProvider disableProbeModes
     *
     * @param bool $hold whether the caller is about to write under the answer
     */
    public function testTheProbeOfAKeysGroupsIsHeldOnlyWhenTheCallerWritesUnderIt(
        bool $hold,
        bool $expectedToBeHeld,
    ): void {
        $captured = null;
        $connection = $this->makeEmpty(Connection::class, [
            'fetchFirstColumn' => static function (string $query) use (&$captured): array {
                $captured = $query;

                return [];
            },
        ]);

        $repository = new KeyGroupRelationRepository(
            $this->makeEmpty(ListingFilterInterface::class),
            $connection,
            $this->makeEmpty(ArtifactEntryMapperInterface::class)
        );

        $repository->getGroupNamesByKeyId(51, $hold);

        $this->assertSame($expectedToBeHeld, str_contains((string) $captured, 'FOR UPDATE'), (string) $captured);
    }

    /**
     * @return array<string, array{bool, bool}>
     */
    public static function disableProbeModes(): array
    {
        return [
            'a real run, which writes under the answer' => [true, true],
            'a dry run, which writes nothing' => [false, false],
        ];
    }

    private function createKeyRepository(): KeyRepository
    {
        $this->stubDatabaseReturning([]);

        return new KeyRepository(
            $this->makeEmpty(KeyConfigResolverInterface::class),
            new InMemoryKeyLocalizationResolver(),
            $this->makeEmpty(ListingFilterInterface::class),
            $this->makeEmpty(SearchHelperServiceInterface::class),
            $this->makeEmpty(Connection::class)
        );
    }
}
