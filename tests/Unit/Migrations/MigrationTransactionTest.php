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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\Migrations;

use Codeception\Test\Unit;
use Doctrine\Migrations\AbstractMigration;
use ReflectionClass;
use function basename;
use function glob;
use function in_array;
use function sprintf;

/**
 * MySQL and MariaDB commit implicitly when they receive a DDL statement. A migration that runs
 * DDL inside the transaction Doctrine wraps it in therefore leaves DBAL counting a transaction
 * that no longer exists: every such migration adds a savepoint the server silently ignored, and
 * the next real transaction on the connection fails with "SAVEPOINT DOCTRINE_n does not exist".
 *
 * Nothing flags a forgotten override when the migration is written, so this walks the migration
 * directory and every migration that issues DDL has to opt out of the transaction.
 *
 * @internal
 */
final class MigrationTransactionTest extends Unit
{
    private const string MIGRATIONS_DIRECTORY = __DIR__ . '/../../../src/Migrations';

    private const string MIGRATIONS_NAMESPACE = 'Pimcore\\Bundle\\StudioBackendBundle\\Migrations\\';

    /**
     * Migrations that only run DML (a single INSERT ... SELECT) and never issue DDL. They have
     * no implicit commit to guard against and keep the default transactional behavior.
     */
    private const array DATA_ONLY_MIGRATIONS = [
        'Version20260115100546',
        'Version20260505124233',
        'Version20260826120000',
    ];

    /**
     * @return array<string, array{string}>
     */
    public function schemaMigrationProvider(): array
    {
        $cases = [];
        foreach ($this->findMigrationNames() as $name) {
            if (!in_array($name, self::DATA_ONLY_MIGRATIONS, true)) {
                $cases[$name] = [$name];
            }
        }

        return $cases;
    }

    /**
     * @dataProvider schemaMigrationProvider
     */
    public function testMigrationsIssuingDdlAreNotTransactional(string $name): void
    {
        $migration = $this->instantiate($name);

        $this->assertFalse(
            $migration->isTransactional(),
            sprintf(
                '%s must override isTransactional() to return false: DDL commits implicitly on MySQL/MariaDB, '
                . 'which leaves the DBAL transaction nesting level out of sync. If the migration only runs DML, '
                . 'add it to DATA_ONLY_MIGRATIONS instead.',
                $name
            )
        );
    }

    public function testTheDataOnlyAllowlistOnlyNamesExistingMigrations(): void
    {
        $existing = $this->findMigrationNames();

        foreach (self::DATA_ONLY_MIGRATIONS as $name) {
            $this->assertContains($name, $existing, sprintf('%s is on the allowlist but no longer exists.', $name));
        }
    }

    /**
     * @return list<string>
     */
    private function findMigrationNames(): array
    {
        $names = [];
        foreach (glob(self::MIGRATIONS_DIRECTORY . '/Version*.php') ?: [] as $file) {
            $names[] = basename($file, '.php');
        }

        $this->assertNotEmpty($names, 'No migrations found, the directory constant is stale.');

        return $names;
    }

    private function instantiate(string $name): AbstractMigration
    {
        $migration = (new ReflectionClass(self::MIGRATIONS_NAMESPACE . $name))->newInstanceWithoutConstructor();
        $this->assertInstanceOf(AbstractMigration::class, $migration);

        return $migration;
    }
}
