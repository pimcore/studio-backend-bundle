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
use Pimcore;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Repository\Configuration\SubGroupRepository;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\SearchHelperServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Listing\Service\ListingFilterInterface;
use Pimcore\Model\DataObject\Classificationstore\SubGroupConfig;
use Pimcore\Model\DataObject\Classificationstore\SubGroupConfig\Dao as SubGroupConfigDao;
use ReflectionProperty;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpKernel\KernelInterface;
use function array_keys;

/**
 * `getByIdentifiers()` is the lookup the group layout resolves its sub-group metadata through, and
 * the one place that keeps a vocabulary entry of another store from answering for this one. The
 * layout tests replace it with a mock, so the restriction itself is only observable here.
 *
 * There is no database in the unit suite, so the store restriction is asserted where it is
 * decided: on the statement that reaches the connection and on the values bound into it. A row of
 * another store cannot come back from a statement that binds this store's id to `storeId`.
 *
 * @internal
 */
final class SubGroupIdentifierLookupTest extends Unit
{
    private const string CONNECTION_SERVICE_ID = 'doctrine.dbal.default_connection';

    private const int STORE_ID = 1;

    private const int OTHER_STORE_ID = 2;

    private const string FIRST_IDENTIFIER = '01JBQ8X9Z0ABCDEFGHJKMNPQRS';

    private const string SECOND_IDENTIFIER = '01JBQ8X9Z0TUVWXYZ23456789';

    /**
     * @var list<array{query: string, params: array<array-key, mixed>}>
     */
    private array $statements = [];

    private ?KernelInterface $kernelBeforeStub = null;

    private bool $kernelStubbed = false;

    /**
     * Several identifiers become one IN list, and the store is bound alongside them. The
     * statement is positional throughout, so the bindings are the store first and then the
     * identifiers in the order they were asked for - a named binding mixed in here would make
     * DBAL refuse the statement.
     */
    public function testSeveralIdentifiersAreLookedUpInOneStoreScopedStatement(): void
    {
        $repository = $this->createRepository([
            $this->createRow(id: 21, identifier: self::FIRST_IDENTIFIER),
            $this->createRow(id: 22, identifier: self::SECOND_IDENTIFIER),
        ]);

        $repository->getByIdentifiers(
            self::STORE_ID,
            [self::FIRST_IDENTIFIER, self::SECOND_IDENTIFIER]
        );

        $statement = $this->getSingleStatement();

        $this->assertStringContainsString(SubGroupConfigDao::TABLE_NAME_SUBGROUPS, $statement['query']);
        $this->assertStringContainsString('`storeId` = ?', $statement['query']);
        $this->assertStringContainsString('`identifier` IN (?,?)', $statement['query']);
        $this->assertSame(
            [self::STORE_ID, self::FIRST_IDENTIFIER, self::SECOND_IDENTIFIER],
            $statement['params']
        );
    }

    /**
     * The result is keyed by identifier, because that is what a layout holds - not the row id.
     */
    public function testTheResultIsKeyedByIdentifier(): void
    {
        $repository = $this->createRepository([
            $this->createRow(id: 21, identifier: self::FIRST_IDENTIFIER),
            $this->createRow(id: 22, identifier: self::SECOND_IDENTIFIER),
        ]);

        $resolved = $repository->getByIdentifiers(
            self::STORE_ID,
            [self::FIRST_IDENTIFIER, self::SECOND_IDENTIFIER]
        );

        $this->assertSame(
            [self::FIRST_IDENTIFIER, self::SECOND_IDENTIFIER],
            array_keys($resolved)
        );
        $this->assertInstanceOf(SubGroupConfig::class, $resolved[self::FIRST_IDENTIFIER]);
        $this->assertSame(21, $resolved[self::FIRST_IDENTIFIER]->getId());
        $this->assertSame(22, $resolved[self::SECOND_IDENTIFIER]->getId());
    }

    /**
     * The identifier is unique per store, not globally: the very same identifier can name a
     * different sub group in another store. Asking store 1 must bind store 1, so store 2's row
     * is never a candidate - this is the boundary that keeps layout metadata from resolving
     * against a neighbouring store.
     */
    public function testAnotherStoresMatchingIdentifierIsExcludedByTheStatement(): void
    {
        $repository = $this->createRepository([]);

        $resolved = $repository->getByIdentifiers(self::OTHER_STORE_ID, [self::FIRST_IDENTIFIER]);

        $statement = $this->getSingleStatement();

        $this->assertStringContainsString('`storeId` = ?', $statement['query']);
        $this->assertSame([self::OTHER_STORE_ID, self::FIRST_IDENTIFIER], $statement['params']);
        $this->assertNotContains(
            self::STORE_ID,
            $statement['params'],
            'Only the requested store may be bound.'
        );
        $this->assertSame([], $resolved);
    }

    /**
     * No identifiers means nothing to resolve. An empty IN list is not valid SQL, so the lookup
     * has to answer without going to the database at all.
     */
    public function testAnEmptyIdentifierListResolvesWithoutAStatement(): void
    {
        $repository = $this->createRepository([$this->createRow()]);

        $this->assertSame([], $repository->getByIdentifiers(self::STORE_ID, []));
        $this->assertSame([], $this->statements, 'An empty input must not query.');
    }

    /**
     * A row whose identifier was never written has nothing a layout could hold it by, so it is
     * left out rather than keyed by an empty string.
     */
    public function testARowWithoutAnIdentifierIsLeftOut(): void
    {
        $repository = $this->createRepository([
            $this->createRow(id: 21, identifier: null),
            $this->createRow(id: 22, identifier: self::SECOND_IDENTIFIER),
        ]);

        $resolved = $repository->getByIdentifiers(
            self::STORE_ID,
            [self::FIRST_IDENTIFIER, self::SECOND_IDENTIFIER]
        );

        $this->assertSame([self::SECOND_IDENTIFIER], array_keys($resolved));
    }

    /**
     * @param list<array<string, mixed>> $rows
     */
    private function createRepository(array $rows): SubGroupRepository
    {
        $connection = $this->makeEmpty(Connection::class, [
            'quoteIdentifier' => static fn (string $identifier): string => '`' . $identifier . '`',
            'fetchAllAssociative' => function (
                string $query,
                array $params = [],
                array $types = []
            ) use ($rows): array {
                $this->statements[] = ['query' => $query, 'params' => $params];

                return $rows;
            },
        ]);

        $container = new Container();
        $container->set(self::CONNECTION_SERVICE_ID, $connection);

        $this->kernelBeforeStub = Pimcore::getKernel();
        $this->kernelStubbed = true;
        Pimcore::setKernel($this->makeEmpty(KernelInterface::class, ['getContainer' => $container]));

        return new SubGroupRepository(
            $this->makeEmpty(ListingFilterInterface::class),
            $this->makeEmpty(SearchHelperServiceInterface::class),
            $connection
        );
    }

    /**
     * @return array{query: string, params: array<array-key, mixed>}
     */
    private function getSingleStatement(): array
    {
        $this->assertCount(1, $this->statements, 'The lookup has to resolve in a single statement.');

        return $this->statements[0];
    }

    /**
     * @return array<string, mixed>
     */
    private function createRow(
        int $id = 21,
        ?string $identifier = self::FIRST_IDENTIFIER,
    ): array {
        return [
            'id' => $id,
            'storeId' => self::STORE_ID,
            'name' => 'Dimensions',
            'description' => 'Physical dimensions',
            'identifier' => $identifier,
            'creationDate' => 1734567890,
            'modificationDate' => 1734567891,
        ];
    }

    protected function _after(): void
    {
        $this->statements = [];

        if (!$this->kernelStubbed) {
            return;
        }

        // The suite is shuffled and Pimcore::setKernel() does not take null, so put the previous
        // kernel back the only way there is.
        (new ReflectionProperty(Pimcore::class, 'kernel'))->setValue(null, $this->kernelBeforeStub);
        $this->kernelStubbed = false;
    }
}
