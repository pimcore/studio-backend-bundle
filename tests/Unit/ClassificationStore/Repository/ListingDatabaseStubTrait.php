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

use Doctrine\DBAL\Connection;
use Pimcore;
use Pimcore\Model\DataObject\Classificationstore\KeyGroupRelationCache;
use ReflectionProperty;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * The repositories build their listings with `new Listing()`, so a listing cannot be injected.
 * This stands in for the connection a listing resolves through `Pimcore\Db`, which lets a test call
 * the repository's public entry point and assert on the statement that reaches the database, and on
 * the rows the repository hands back. There is no database in the unit suite, so the stub returns
 * its rows verbatim: the ORDER BY is the observable part of the ordering contract, the row order is
 * what proves the repository passes the result through unsorted.
 *
 * The container also holds the relation cache, which the classification store DAOs resolve from it
 * rather than being handed it.
 *
 * Writes are recorded apart from reads by {@see getExecutedWrites()}, so a guard that must refuse
 * before it changes anything can be held to it: with no database behind the suite, "the rows are
 * intact" is provable only as "no statement that could change them was issued".
 *
 * @internal
 */
trait ListingDatabaseStubTrait
{
    private const string CONNECTION_SERVICE_ID = 'doctrine.dbal.default_connection';

    /**
     * @var list<string>
     */
    private array $executedQueries = [];

    /**
     * @var list<string>
     */
    private array $executedWrites = [];

    private ?KernelInterface $kernelBeforeStub = null;

    private bool $kernelStubbed = false;

    /**
     * @param list<array<string, mixed>> $rows
     */
    private function stubDatabaseReturning(array $rows): void
    {
        $this->stubDatabaseAnswering(static fn (string $query): array => $rows);
    }

    /**
     * For an entry point whose statements read more than one table - a guard that probes a second
     * one before it writes, say: the callable is handed each query and answers it with the rows
     * that table holds.
     *
     * @param callable(string): list<array<string, mixed>> $answer
     */
    private function stubDatabaseAnswering(callable $answer): void
    {
        $connection = $this->makeEmpty(Connection::class, [
            'quoteIdentifier' => static fn (string $identifier): string => '`' . $identifier . '`',
            'quote' => static fn (string $value): string => "'" . $value . "'",
            'fetchAllAssociative' => function (string $query) use ($answer): array {
                $this->executedQueries[] = $query;

                return $answer($query);
            },
            'fetchAssociative' => static fn (string $query): array|false => $answer($query)[0] ?? false,
            'delete' => function (string $table): int {
                $this->executedWrites[] = 'DELETE ' . $table;

                return 0;
            },
            'update' => function (string $table): int {
                $this->executedWrites[] = 'UPDATE ' . $table;

                return 0;
            },
            'executeStatement' => function (string $query): int {
                $this->executedWrites[] = $query;

                return 0;
            },
        ]);

        $container = new Container();
        $container->set(self::CONNECTION_SERVICE_ID, $connection);
        $container->set(KeyGroupRelationCache::class, new KeyGroupRelationCache());

        $this->kernelBeforeStub = Pimcore::getKernel();
        $this->kernelStubbed = true;
        Pimcore::setKernel($this->makeEmpty(KernelInterface::class, ['getContainer' => $container]));
    }

    private function getExecutedQuery(): string
    {
        $this->assertCount(1, $this->executedQueries, 'Expected the entry point to run a single query.');

        return $this->executedQueries[0];
    }

    /**
     * @return list<string>
     */
    private function getExecutedQueries(): array
    {
        return $this->executedQueries;
    }

    /**
     * @return list<string>
     */
    private function getExecutedWrites(): array
    {
        return $this->executedWrites;
    }

    protected function _after(): void
    {
        $this->executedQueries = [];
        $this->executedWrites = [];

        if (!$this->kernelStubbed) {
            return;
        }

        // The suite is shuffled and Pimcore::setKernel() does not take null, so put the previous
        // kernel back the only way there is.
        (new ReflectionProperty(Pimcore::class, 'kernel'))->setValue(null, $this->kernelBeforeStub);
        $this->kernelStubbed = false;
    }
}
