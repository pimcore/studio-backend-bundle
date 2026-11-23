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
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception as DBALException;
use Doctrine\DBAL\ParameterType;
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\ClassificationStore\GroupConfigResolverInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\ClassificationStore\KeyConfigResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Repository\Configuration\GroupRepository;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Repository\Configuration\GroupRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Repository\Configuration\KeyRepository;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Repository\Configuration\KeyRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\SearchHelperServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\DatabaseException;
use Pimcore\Bundle\StudioBackendBundle\Listing\Service\ListingFilterInterface;
use Pimcore\Bundle\StudioBackendBundle\Tests\Unit\ClassificationStore\Service\InMemoryKeyLocalizationResolver;
use function sprintf;
use function str_contains;

/**
 * The two identifier lookups of the group and key configuration repositories, asserted on the
 * statement that reaches the database and on what the repository makes of the rows it gets back.
 *
 * The caller these serve - the grid and saved-search column references - reads them per store and
 * per request, so three things are part of their contract and none of them is observable from the
 * service that calls them: the read is scoped to the store it was asked for, the identifiers or ids
 * are bound as ONE list parameter (a reference a configuration names twice must not become two
 * statements, nor a bound list with holes in its keys), and the reverse direction leaves out the
 * rows whose identifier is still NULL - a store that has not been stamped yet holds many of those,
 * and a row without an identifier has nothing to be written into a configuration.
 *
 * There is no database in the unit suite, so the NULL exclusion is asserted where it lives, which
 * is the predicate: no stub can filter rows a real statement would never have returned.
 *
 * @internal
 */
final class IdentifierLookupsTest extends Unit
{
    private const string GROUP = 'group';

    private const string KEY = 'key';

    private const int STORE_ID = 1;

    private const string GROUPS_TABLE = 'classificationstore_groups';

    private const string KEYS_TABLE = 'classificationstore_keys';

    private const string FIRST_IDENTIFIER = '01JBQ8X9Z0ABCDEFGHJKMNPQRS';

    private const string SECOND_IDENTIFIER = '01JBQ8X9Z0ABCDEFGHJKMNPQRT';

    /**
     * Every statement the repository ran, in order.
     *
     * @var list<array{query: string, parameters: array<int, mixed>, types: array<int, mixed>}>
     */
    private array $statements = [];

    protected function _before(): void
    {
        $this->statements = [];
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function entities(): array
    {
        return [
            'groups' => [self::GROUP, self::GROUPS_TABLE],
            'keys' => [self::KEY, self::KEYS_TABLE],
        ];
    }

    /**
     * @dataProvider entities
     */
    public function testTheIdentifierLookupIsScopedToTheStoreAndBindsOneDeduplicatedList(
        string $entity,
        string $table
    ): void {
        $repository = $this->createRepository($entity, $this->createConnection([
            ['id' => '5', 'identifier' => self::FIRST_IDENTIFIER],
            ['id' => '6', 'identifier' => self::SECOND_IDENTIFIER],
        ]));

        $resolved = $repository->getIdsByIdentifiers(
            self::STORE_ID,
            [self::FIRST_IDENTIFIER, self::SECOND_IDENTIFIER, self::FIRST_IDENTIFIER]
        );

        $statement = $this->getSingleStatement();
        $this->assertTrue(
            str_contains(
                $statement['query'],
                sprintf('FROM `%s` WHERE `storeId` = ? AND `identifier` IN (?)', $table)
            ),
            $statement['query']
        );
        $this->assertSame(
            [self::STORE_ID, [self::FIRST_IDENTIFIER, self::SECOND_IDENTIFIER]],
            $statement['parameters']
        );
        $this->assertSame([ParameterType::INTEGER, ArrayParameterType::STRING], $statement['types']);
        $this->assertSame([self::FIRST_IDENTIFIER => 5, self::SECOND_IDENTIFIER => 6], $resolved);
    }

    /**
     * @dataProvider entities
     */
    public function testTheReverseLookupIsScopedToTheStoreAndBindsOneDeduplicatedList(
        string $entity,
        string $table
    ): void {
        $repository = $this->createRepository($entity, $this->createConnection([
            ['id' => '5', 'identifier' => self::FIRST_IDENTIFIER],
            ['id' => '6', 'identifier' => self::SECOND_IDENTIFIER],
        ]));

        $identifiers = $repository->getIdentifiersByIds(self::STORE_ID, [5, 6, 5]);

        $statement = $this->getSingleStatement();
        $this->assertTrue(
            str_contains($statement['query'], sprintf('FROM `%s` WHERE `storeId` = ? AND `id` IN (?)', $table)),
            $statement['query']
        );
        $this->assertSame([self::STORE_ID, [5, 6]], $statement['parameters']);
        $this->assertSame([ParameterType::INTEGER, ArrayParameterType::INTEGER], $statement['types']);
        $this->assertSame([5 => self::FIRST_IDENTIFIER, 6 => self::SECOND_IDENTIFIER], $identifiers);
    }

    /**
     * A row whose identifier is NULL is not a row this lookup can answer with, and it is left out by
     * the statement rather than by a filter over the result - so that it is not counted, paged or
     * mapped to an empty string anywhere on the way back.
     *
     * @dataProvider entities
     */
    public function testTheReverseLookupExcludesTheRowsWithoutAnIdentifier(string $entity): void
    {
        $repository = $this->createRepository($entity, $this->createConnection([]));

        $repository->getIdentifiersByIds(self::STORE_ID, [5]);

        $statement = $this->getSingleStatement();
        $this->assertTrue(str_contains($statement['query'], '`identifier` IS NOT NULL'), $statement['query']);
    }

    /**
     * The forward direction takes the identifiers it was given, so it carries no such condition: an
     * identifier that is NULL cannot be asked for in the first place.
     *
     * @dataProvider entities
     */
    public function testTheIdentifierLookupCarriesNoNullCondition(string $entity): void
    {
        $repository = $this->createRepository($entity, $this->createConnection([]));

        $repository->getIdsByIdentifiers(self::STORE_ID, [self::FIRST_IDENTIFIER]);

        $statement = $this->getSingleStatement();
        $this->assertFalse(str_contains($statement['query'], '`identifier` IS NOT NULL'), $statement['query']);
    }

    /**
     * An empty IN () list is not valid SQL, and a column configuration that names no reference at
     * all is the ordinary case, so neither direction spends a statement on it.
     *
     * @dataProvider entities
     */
    public function testNeitherDirectionQueriesForAnEmptyInput(string $entity): void
    {
        $repository = $this->createRepository($entity, $this->createConnection([
            ['id' => '5', 'identifier' => self::FIRST_IDENTIFIER],
        ]));

        $this->assertSame([], $repository->getIdsByIdentifiers(self::STORE_ID, []));
        $this->assertSame([], $repository->getIdentifiersByIds(self::STORE_ID, []));
        $this->assertSame([], $this->statements);
    }

    /**
     * @dataProvider entities
     */
    public function testTheIdentifierLookupWrapsADatabaseFailure(string $entity): void
    {
        $repository = $this->createRepository($entity, $this->createFailingConnection());

        $this->expectException(DatabaseException::class);

        $repository->getIdsByIdentifiers(self::STORE_ID, [self::FIRST_IDENTIFIER]);
    }

    /**
     * @dataProvider entities
     */
    public function testTheReverseLookupWrapsADatabaseFailure(string $entity): void
    {
        $repository = $this->createRepository($entity, $this->createFailingConnection());

        $this->expectException(DatabaseException::class);

        $repository->getIdentifiersByIds(self::STORE_ID, [5]);
    }

    private function createRepository(
        string $entity,
        Connection $connection
    ): GroupRepositoryInterface|KeyRepositoryInterface {
        if ($entity === self::GROUP) {
            return new GroupRepository(
                $this->makeEmpty(GroupConfigResolverInterface::class),
                $this->makeEmpty(ListingFilterInterface::class),
                $this->makeEmpty(SearchHelperServiceInterface::class),
                $connection
            );
        }

        return new KeyRepository(
            $this->makeEmpty(KeyConfigResolverInterface::class),
            new InMemoryKeyLocalizationResolver(),
            $this->makeEmpty(ListingFilterInterface::class),
            $this->makeEmpty(SearchHelperServiceInterface::class),
            $connection
        );
    }

    /**
     * @param list<array<string, mixed>> $rows the rows the statement answers with, as strings, which
     *                                         is how a database hands them back
     */
    private function createConnection(array $rows): Connection
    {
        return $this->makeEmpty(Connection::class, [
            'fetchAllAssociative' => function (
                string $query,
                array $parameters = [],
                array $types = []
            ) use ($rows): array {
                $this->statements[] = ['query' => $query, 'parameters' => $parameters, 'types' => $types];

                return $rows;
            },
        ]);
    }

    private function createFailingConnection(): Connection
    {
        $failure = $this->createMock(DBALException::class);

        return $this->makeEmpty(Connection::class, [
            'fetchAllAssociative' => static function () use ($failure): array {
                throw $failure;
            },
        ]);
    }

    /**
     * @return array{query: string, parameters: array<int, mixed>, types: array<int, mixed>}
     */
    private function getSingleStatement(): array
    {
        $this->assertCount(1, $this->statements, 'Expected the lookup to run a single statement.');

        return $this->statements[0];
    }
}
