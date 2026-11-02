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

use Doctrine\DBAL\Connection;
use Pimcore;
use Pimcore\Cache\RuntimeCache;
use Pimcore\Model\Dao\AbstractDao;
use Pimcore\Model\DataObject\Classificationstore\KeyGroupRelation\Dao;
use Pimcore\Model\DataObject\Classificationstore\KeyGroupRelationCache;
use ReflectionProperty;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpKernel\KernelInterface;
use function array_shift;
use function ltrim;
use function rtrim;

/**
 * The repository writes through `KeyGroupRelation`, which resolves its connection through `Pimcore\Db`
 * and cannot be injected. This stands in for that connection, so a test can call the public entry point
 * and assert on the row that reaches the database - which is where "an absent property is not written"
 * and "the resolved sub group identifier is written, not the submitted one" are observable at all.
 *
 * There is no database in the unit suite, so the column list the write path reads is pre-seeded into the
 * runtime cache the way a first read would leave it, which keeps `SHOW COLUMNS` out of the stub.
 *
 * @internal
 */
trait RelationWriteDatabaseStubTrait
{
    private const string CONNECTION_SERVICE_ID = 'doctrine.dbal.default_connection';

    private const array RELATION_COLUMNS = [
        'groupId',
        'keyId',
        'sorter',
        'mandatory',
        'metadata',
        'definitionOverride',
        'subGroup',
    ];

    /**
     * @var list<array<string, mixed>>
     */
    private array $writtenRows = [];

    private ?KernelInterface $kernelBeforeStub = null;

    private bool $kernelStubbed = false;

    /**
     * @param list<array<string, mixed>> $rows the rows the relation listing loads
     * @param list<mixed> $singleValues answers for the reads the sub group resolution makes, in order
     */
    private function stubDatabase(array $rows, array $singleValues = []): void
    {
        $connection = $this->makeEmpty(Connection::class, [
            'quoteIdentifier' => static fn (string $identifier): string => '`' . $identifier . '`',
            'fetchAllAssociative' => static fn (): array => $rows,
            'fetchOne' => static function () use (&$singleValues): mixed {
                return array_shift($singleValues);
            },
            'isTransactionActive' => static fn (): bool => false,
            'beginTransaction' => static fn (): bool => true,
            'commit' => static fn (): bool => true,
            'rollBack' => static fn (): bool => true,
            'insert' => function (string $table, array $data): int {
                $this->writtenRows[] = $data;

                return 1;
            },
            'lastInsertId' => static fn (): string => '0',
        ]);

        $container = new Container();
        $container->set(self::CONNECTION_SERVICE_ID, $connection);
        $container->set(KeyGroupRelationCache::class, new KeyGroupRelationCache());

        $this->kernelBeforeStub = Pimcore::getKernel();
        $this->kernelStubbed = true;
        Pimcore::setKernel($this->makeEmpty(KernelInterface::class, ['getContainer' => $container]));

        RuntimeCache::set($this->getColumnCacheKey(), [
            'columns' => self::RELATION_COLUMNS,
            'primaryKeyColumns' => ['groupId', 'keyId'],
        ]);
    }

    /**
     * The row the write path handed to the database, with the quoting of its column names undone.
     *
     * @return array<string, mixed>
     */
    private function getWrittenRow(): array
    {
        $this->assertCount(1, $this->writtenRows, 'Expected the entry point to write a single row.');

        $row = [];
        foreach ($this->writtenRows[0] as $column => $value) {
            $row[ltrim(rtrim($column, '`'), '`')] = $value;
        }

        return $row;
    }

    private function assertNothingWasWritten(): void
    {
        $this->assertSame([], $this->writtenRows, 'Expected the refusal to leave the row alone.');
    }

    protected function _after(): void
    {
        $this->writtenRows = [];

        if (!$this->kernelStubbed) {
            return;
        }

        RuntimeCache::getInstance()->offsetUnset($this->getColumnCacheKey());

        // The suite is shuffled and Pimcore::setKernel() does not take null, so put the previous
        // kernel back the only way there is.
        (new ReflectionProperty(Pimcore::class, 'kernel'))->setValue(null, $this->kernelBeforeStub);
        $this->kernelStubbed = false;
    }

    private function getColumnCacheKey(): string
    {
        return AbstractDao::CACHEKEY . Dao::TABLE_NAME_RELATIONS;
    }
}
