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
use Doctrine\DBAL\Driver\Exception as DriverException;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Exception;
use Pimcore;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Repository\Configuration\SubGroupRepository;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\SearchHelperServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ElementSavingFailedException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Listing\Service\ListingFilterInterface;
use Pimcore\Cache\RuntimeCache;
use Pimcore\Model\Dao\AbstractDao;
use Pimcore\Model\DataObject\Classificationstore\KeyGroupRelationCache;
use Pimcore\Model\DataObject\Classificationstore\StoreConfig\Dao as StoreConfigDao;
use Pimcore\Model\DataObject\Classificationstore\SubGroupConfig\Dao as SubGroupConfigDao;
use ReflectionProperty;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpKernel\KernelInterface;
use function implode;
use function sort;
use function sprintf;
use function str_contains;

/**
 * The name of a sub group is unique per store, and nothing in the schema enforces that pair: the
 * probe and the write are two statements, so what keeps two creates of the same name - or two
 * renames onto it - from both persisting is the unit of work they run in.
 *
 * There is no database in the unit suite, so the competing writer is modelled where it is actually
 * observed: a row that the probe only reports once the store has been locked is a row another
 * writer committed while this one waited for that lock. A probe that runs before the lock, or that
 * answers from a snapshot taken before it, cannot see it - which is the state this guards against.
 *
 * @internal
 */
final class SubGroupNameUniquenessTest extends Unit
{
    private const string CONNECTION_SERVICE_ID = 'doctrine.dbal.default_connection';

    private const int STORE_ID = 1;

    private const int SUB_GROUP_ID = 21;

    private const int WINNER_ID = 22;

    private const string SUB_GROUP_NAME = 'Dimensions';

    private const string IDENTIFIER = '01JBQ8X9Z0ABCDEFGHJKMNPQRS';

    /**
     * Every step the save took, in the order it took them.
     *
     * @var list<string>
     */
    private array $journal = [];

    /**
     * @var list<string>
     */
    private array $writes = [];

    private bool $inTransaction = false;

    private bool $storeHeld = false;

    private int $probes = 0;

    private ?KernelInterface $kernelBeforeStub = null;

    private bool $kernelStubbed = false;

    /**
     * The store is taken first, then the name is probed against the rows as of now, and only then is
     * anything written - all of it inside one transaction, which is what makes two writers of the
     * same name exclusive rather than merely unlikely to overlap.
     */
    public function testTheProbeAndTheWriteRunAsOneLockedUnitOfWork(): void
    {
        $repository = $this->createRepository(savingANewRow: true);

        $repository->create(self::SUB_GROUP_NAME, self::STORE_ID);

        $this->assertStepsInOrder([
            'begin',
            StoreConfigDao::TABLE_NAME_STORES,
            '`name` =',
            'INSERT ' . SubGroupConfigDao::TABLE_NAME_SUBGROUPS,
            'commit',
        ]);

        $this->assertStringContainsString(
            'FOR UPDATE',
            $this->firstStepContaining(StoreConfigDao::TABLE_NAME_STORES),
            'The store has to be held, not just read.'
        );
        $this->assertStringContainsString(
            'FOR UPDATE',
            $this->firstStepContaining('`name` ='),
            'The probe has to read as of now rather than from the snapshot of its transaction.'
        );
    }

    /**
     * Two creates of the same name: the one that reaches the store second finds the row the first one
     * left and is refused with the 422 the API promises, instead of writing a second row beside it.
     */
    public function testTheCreateThatLosesTheRaceForTheNameIsRefused(): void
    {
        $repository = $this->createRepository(
            // committed by the writer that held the store first
            probe: static fn (int $call, bool $storeHeld): int|false => $storeHeld ? self::WINNER_ID : false
        );

        try {
            $repository->create(self::SUB_GROUP_NAME, self::STORE_ID);
            $this->fail('Expected the writer that lost the name to be refused.');
        } catch (InvalidArgumentException $e) {
            $this->assertSame(422, $e->getStatusCode());
            $this->assertStringContainsString(self::SUB_GROUP_NAME, $e->getMessage());
            $this->assertStringContainsString('store 1', $e->getMessage());
        }

        $this->assertSame([], $this->writes, 'The writer that lost the name must not write.');
        $this->assertStepsInOrder(['begin', StoreConfigDao::TABLE_NAME_STORES, '`name` =', 'rollback']);
    }

    /**
     * The same race between two renames, where the row being updated is excluded from the probe and
     * the one the other writer renamed is not.
     */
    public function testTheRenameThatLosesTheRaceForTheNameIsRefused(): void
    {
        $repository = $this->createRepository(
            storedRows: [$this->createRow()],
            probe: static fn (int $call, bool $storeHeld): int|false => $storeHeld ? self::WINNER_ID : false
        );

        try {
            $repository->update(self::SUB_GROUP_ID, 'Measurements', null);
            $this->fail('Expected the rename that lost the name to be refused.');
        } catch (InvalidArgumentException $e) {
            $this->assertSame(422, $e->getStatusCode());
            $this->assertStringContainsString('Measurements', $e->getMessage());
        }

        $this->assertSame([], $this->writes, 'The rename that lost the name must not write.');
        $this->assertStepsInOrder(['begin', StoreConfigDao::TABLE_NAME_STORES, '`name` =', 'rollback']);
        $this->assertStringContainsString(
            '`id` !=',
            $this->firstStepContaining('`name` ='),
            'The probe has to exclude the row being renamed.'
        );
    }

    /**
     * An install whose schema enforces the pair itself refuses the losing writer rather than letting
     * the probe catch it. That is the same collision, and the answer is the same 422 - not the 500 a
     * failed save would otherwise become. Which pair the schema refused is read back: here the name
     * is taken, so a name collision is what it was.
     */
    public function testANameCollisionRefusedByTheSchemaIsReportedAsADuplicate(): void
    {
        $repository = $this->createRepository(
            savingANewRow: true,
            // free when probed, taken by the time the refusal is read back
            probe: static fn (int $call): int|false => $call === 1 ? false : self::WINNER_ID,
            refuseTheWrite: $this->uniqueViolation('storeId_name')
        );

        try {
            $repository->create(self::SUB_GROUP_NAME, self::STORE_ID);
            $this->fail('Expected the collision the schema refused to be reported as a duplicate name.');
        } catch (InvalidArgumentException $e) {
            $this->assertSame(422, $e->getStatusCode());
            $this->assertStringContainsString(self::SUB_GROUP_NAME, $e->getMessage());
        }

        $this->assertStepsInOrder(['begin', '`name` =', 'rollback']);
    }

    /**
     * The table carries a unique index on the identifier as well, and that collision is not a
     * collision of the name: the name is still free when it is read back, so the save's own failure
     * stands rather than being reported as a duplicate name the client could do something about.
     */
    public function testAUniqueViolationThatIsNotTheNameStillFailsTheSave(): void
    {
        $repository = $this->createRepository(
            savingANewRow: true,
            refuseTheWrite: $this->uniqueViolation('storeId_identifier')
        );

        try {
            $repository->create(self::SUB_GROUP_NAME, self::STORE_ID);
            $this->fail('Expected the save to fail.');
        } catch (ElementSavingFailedException $e) {
            $this->assertSame(500, $e->getStatusCode());
            $this->assertStringContainsString('storeId_identifier', $e->getMessage());
        }
    }

    /**
     * An apply writes through the same unit of work. The rule is the sub group's, not the
     * endpoint's: a file that names two identifiers the same way, or an apply racing a create from
     * the UI, would otherwise persist the duplicate every other path refuses.
     */
    public function testTheArtifactSaveTakesTheSameLockedPath(): void
    {
        $repository = $this->createRepository(savingANewRow: true);

        $repository->saveFromArtifact(null, self::STORE_ID, self::IDENTIFIER, self::SUB_GROUP_NAME, null);

        $this->assertStepsInOrder([
            'begin',
            StoreConfigDao::TABLE_NAME_STORES,
            '`name` =',
            'INSERT ' . SubGroupConfigDao::TABLE_NAME_SUBGROUPS,
            'commit',
        ]);
    }

    /**
     * And is refused by it: the apply that reaches the store second finds the row the first writer
     * left and is told the name is taken, instead of writing a second row under it.
     */
    public function testTheArtifactSaveThatLosesTheRaceForTheNameIsRefused(): void
    {
        $repository = $this->createRepository(
            savingANewRow: true,
            probe: static fn (int $call, bool $storeHeld): int|false => $storeHeld ? self::WINNER_ID : false
        );

        try {
            $repository->saveFromArtifact(null, self::STORE_ID, self::IDENTIFIER, self::SUB_GROUP_NAME, null);
            $this->fail('Expected the artifact save that lost the name to be refused.');
        } catch (InvalidArgumentException $e) {
            $this->assertSame(422, $e->getStatusCode());
            $this->assertStringContainsString(self::SUB_GROUP_NAME, $e->getMessage());
        }

        $this->assertSame([], $this->writes, 'The apply that lost the name must not write.');
        $this->assertStepsInOrder(['begin', StoreConfigDao::TABLE_NAME_STORES, '`name` =', 'rollback']);
    }

    /**
     * The probe a reconcile asks before it decides anything reads the same pair without holding it:
     * it has no row of its own to protect at that point, and the decision it goes on to write under
     * is taken under the locking read above.
     */
    public function testTheReconcileProbeReadsTheNameWithoutHoldingIt(): void
    {
        $repository = $this->createRepository(probe: static fn (): int|false => self::WINNER_ID);

        $this->assertTrue($repository->isNameTaken(self::STORE_ID, self::SUB_GROUP_NAME, null));
        $this->assertStringNotContainsString('FOR UPDATE', $this->firstStepContaining('`name` ='));
        $this->assertSame([], $this->writes);
    }

    /**
     * @param list<array<string, mixed>> $storedRows the rows a listing reads back, for `getById()`
     * @param null|callable(int, bool): (int|false) $probe what the duplicate probe answers, by the
     *        number of the call and whether the store is held by then; `false` is a free name
     */
    private function createRepository(
        array $storedRows = [],
        ?callable $probe = null,
        bool $savingANewRow = false,
        ?Exception $refuseTheWrite = null,
    ): SubGroupRepository {
        $probe ??= static fn (): int|false => false;

        // a create writes into the placeholder row it just inserted, which carries neither yet
        $storedStoreId = $savingANewRow ? null : self::STORE_ID;
        $storedIdentifier = $savingANewRow ? null : self::IDENTIFIER;

        $connection = $this->makeEmpty(Connection::class, [
            'quoteIdentifier' => static fn (string $identifier): string => '`' . $identifier . '`',
            'quote' => static fn (string $value): string => "'" . $value . "'",
            'isTransactionActive' => fn (): bool => $this->inTransaction,
            'beginTransaction' => function (): void {
                $this->inTransaction = true;
                $this->journal[] = 'begin';
            },
            'commit' => function (): void {
                $this->inTransaction = false;
                $this->journal[] = 'commit';
            },
            'rollBack' => function (): void {
                $this->inTransaction = false;
                $this->journal[] = 'rollback';
            },
            'fetchAllAssociative' => function (string $query) use ($storedRows): array {
                $this->journal[] = $query;

                return $storedRows;
            },
            'fetchFirstColumn' => function (string $query): array {
                $this->journal[] = $query;

                return [];
            },
            'fetchAssociative' => static fn (): array => [
                'storeId' => self::STORE_ID,
                'identifier' => self::IDENTIFIER,
            ],
            'fetchOne' => function (
                string $query
            ) use ($probe, $storedStoreId, $storedIdentifier): bool|int|string|null {
                $this->journal[] = $query;

                if (str_contains($query, StoreConfigDao::TABLE_NAME_STORES)) {
                    $this->storeHeld = true;

                    return self::STORE_ID;
                }

                if (str_contains($query, '`name` =')) {
                    $this->probes++;

                    return $probe($this->probes, $this->storeHeld);
                }

                return match (true) {
                    // core reads the stored identifier back to enforce that it is written once
                    str_contains($query, 'SELECT `identifier`') => $storedIdentifier,
                    str_contains($query, 'SELECT `storeId`') => $storedStoreId,
                    // the identifier is unique per store and this one is not taken by another row
                    str_contains($query, 'SELECT id FROM') => false,
                    default => null,
                };
            },
            'executeStatement' => function (string $query) use ($refuseTheWrite): int {
                $this->recordWrite($query, $refuseTheWrite);

                return 1;
            },
            'insert' => function (string $table): int {
                // the placeholder row, which carries no name yet and so collides with nothing
                $this->recordWrite('INSERT ' . $table, null);

                return 1;
            },
            'update' => function (string $table) use ($refuseTheWrite): int {
                $this->recordWrite('UPDATE ' . $table, $refuseTheWrite);

                return 1;
            },
            'delete' => function (string $table): int {
                $this->recordWrite('DELETE ' . $table, null);

                return 1;
            },
            'lastInsertId' => static fn (): string => (string) self::SUB_GROUP_ID,
        ]);

        // The model's DAO asks for the table's columns before writing. Registering them in the
        // runtime cache keeps that lookup away from the core cache handler, which the stub
        // container has no reason to carry.
        RuntimeCache::set(
            AbstractDao::CACHEKEY . SubGroupConfigDao::TABLE_NAME_SUBGROUPS,
            [
                'columns' => [
                    'id', 'storeId', 'name', 'description', 'identifier', 'creationDate', 'modificationDate',
                ],
                'primaryKeyColumns' => ['id'],
            ]
        );

        $container = new Container();
        $container->set(self::CONNECTION_SERVICE_ID, $connection);
        // final and constructor-less, so core gets the real one to drop its groups from
        $container->set(KeyGroupRelationCache::class, new KeyGroupRelationCache());

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
     * @return array<string, mixed>
     */
    private function createRow(): array
    {
        return [
            'id' => self::SUB_GROUP_ID,
            'storeId' => self::STORE_ID,
            'name' => self::SUB_GROUP_NAME,
            'description' => 'Physical dimensions',
            'identifier' => self::IDENTIFIER,
            'creationDate' => 1734567890,
            'modificationDate' => 1734567891,
        ];
    }

    /**
     * @throws Exception
     */
    private function recordWrite(string $step, ?Exception $refusal): void
    {
        if ($refusal !== null) {
            throw $refusal;
        }

        $this->writes[] = $step;
        $this->journal[] = $step;
    }

    /**
     * The driver's own answer to a write the schema refuses, built rather than mocked so that the
     * key it names is the one the repository reads back.
     */
    private function uniqueViolation(string $key): UniqueConstraintViolationException
    {
        $message = sprintf('Duplicate entry for key \'%s\'', $key);

        $driverException = new class($message) extends Exception implements DriverException {
            public function getSQLState(): ?string
            {
                return '23000';
            }
        };

        return new UniqueConstraintViolationException($driverException, null);
    }

    /**
     * @param list<string> $needles
     */
    private function assertStepsInOrder(array $needles): void
    {
        $positions = [];
        foreach ($needles as $needle) {
            $position = $this->positionOf($needle);
            $this->assertNotNull(
                $position,
                sprintf('Expected a step containing "%s", took: %s', $needle, implode(' | ', $this->journal))
            );
            $positions[] = $position;
        }

        $inOrder = $positions;
        sort($inOrder);

        $this->assertSame(
            $inOrder,
            $positions,
            'The steps did not run in that order, they ran: ' . implode(' | ', $this->journal)
        );
    }

    private function firstStepContaining(string $needle): string
    {
        $position = $this->positionOf($needle);
        $this->assertNotNull(
            $position,
            sprintf('Expected a step containing "%s", took: %s', $needle, implode(' | ', $this->journal))
        );

        return $this->journal[$position];
    }

    private function positionOf(string $needle): ?int
    {
        foreach ($this->journal as $position => $step) {
            if (str_contains($step, $needle)) {
                return $position;
            }
        }

        return null;
    }

    protected function _after(): void
    {
        $this->journal = [];
        $this->writes = [];
        $this->inTransaction = false;
        $this->storeHeld = false;
        $this->probes = 0;

        if (!$this->kernelStubbed) {
            return;
        }

        // The suite is shuffled and Pimcore::setKernel() does not take null, so put the previous
        // kernel back the only way there is.
        (new ReflectionProperty(Pimcore::class, 'kernel'))->setValue(null, $this->kernelBeforeStub);
        $this->kernelStubbed = false;
    }
}
