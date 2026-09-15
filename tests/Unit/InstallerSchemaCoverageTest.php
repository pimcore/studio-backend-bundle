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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit;

use Codeception\Test\Unit;
use Doctrine\ORM\Mapping\Entity;
use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionClass;
use SplFileInfo;
use function count;
use function file_get_contents;
use function in_array;
use function sprintf;
use function str_contains;
use function str_replace;
use function strlen;
use function substr;

/**
 * Every table this bundle owns has to exist in two places, because they answer different
 * questions: the installer is the fresh-install path, and a migration is the upgrade path
 * for installations that already exist. Nothing in the Pimcore install flow runs or marks
 * bundle migrations, so a table present only in migrations is simply absent on a fresh
 * install, and one present only in the installer never reaches an existing one.
 *
 * Both gaps are quiet. They surface as a TableNotFoundException the first time the feature
 * is used, on whichever half of the estate was missed, which is rarely the one being
 * tested. This asserts the pairing structurally rather than testing any one table.
 *
 * @internal
 */
final class InstallerSchemaCoverageTest extends Unit
{
    private const string ENTITY_DIR = __DIR__ . '/../../src/Entity';

    private const string MIGRATIONS_DIR = __DIR__ . '/../../src/Migrations';

    private const string INSTALLER = __DIR__ . '/../../src/Installer.php';

    private const string NAMESPACE_PREFIX = 'Pimcore\\Bundle\\StudioBackendBundle\\Entity\\';

    /**
     * Tables created before the bundle had a migrations directory at all. The first
     * migration is Version20250616120000 (2025-06-16); the installer gained
     * `bundle_studio_grid_configuration_shares` on 2024-08-20 and
     * `bundle_studio_perspectives_user_perspectives` on 2025-03-20, so there was nothing
     * to pair them with at the time.
     *
     * Listed rather than skipped so that a new entity cannot join them by accident. Adding
     * to this list should take an argument.
     *
     * @var list<string>
     */
    private const array PREDATE_MIGRATIONS = [
        'bundle_studio_grid_configuration_shares',
        'bundle_studio_perspectives_user_perspectives',
    ];

    /**
     * @dataProvider entityTableProvider
     */
    public function testEveryEntityTableIsCreatedAndDroppedByTheInstaller(string $class, string $table): void
    {
        $installer = (string) file_get_contents(self::INSTALLER);
        $reference = $this->shortName($class) . '::TABLE_NAME';

        $this->assertTrue(
            str_contains($installer, $reference),
            sprintf('%s (%s) is never referenced in Installer.php, so a fresh install lacks it.', $class, $table),
        );

        // install() calls a builder method and the constant appears inside the builder, so
        // the two halves are told apart by which side of uninstall() the references fall
        // on rather than by looking inside install() itself.
        $uninstall = $this->methodBody($installer, 'uninstall');
        $elsewhere = str_replace($uninstall, '', $installer);

        $this->assertTrue(
            str_contains($uninstall, $reference),
            sprintf('%s (%s) is never dropped by Installer::uninstall().', $class, $table),
        );

        $this->assertTrue(
            str_contains($elsewhere, $reference),
            sprintf('%s (%s) is dropped but never created by the installer.', $class, $table),
        );
    }

    /**
     * @dataProvider entityTableProvider
     */
    public function testEveryEntityTableHasAMigration(string $class, string $table): void
    {
        if (in_array($table, self::PREDATE_MIGRATIONS, true)) {
            $this->assertTrue(true, 'Predates the bundle\'s migrations directory; see PREDATE_MIGRATIONS.');

            return;
        }

        $this->assertTrue(
            $this->migrationsMention($this->shortName($class) . '::TABLE_NAME') || $this->migrationsMention($table),
            sprintf('%s (%s) has no migration, so an existing installation never gets it.', $class, $table),
        );
    }

    /**
     * Entities are discovered by their `#[ORM\Entity]` attribute rather than by a list, so
     * a table with no entity behind it (the translation table, for instance) is excluded
     * by construction and a new entity is picked up without anyone remembering to add it.
     *
     * @return array<string, array{0: string, 1: string}>
     */
    public static function entityTableProvider(): array
    {
        $cases = [];

        foreach (self::entityFiles() as $file) {
            $class = self::classFor($file);

            if (!class_exists($class)) {
                continue;
            }

            $reflection = new ReflectionClass($class);

            if ($reflection->getAttributes(Entity::class) === []) {
                continue;
            }

            if (!$reflection->hasConstant('TABLE_NAME')) {
                continue;
            }

            /** @var string $table */
            $table = $reflection->getConstant('TABLE_NAME');
            $cases[$reflection->getShortName()] = [$class, $table];
        }

        return $cases;
    }

    public function testTheProviderActuallyFoundEntities(): void
    {
        // A provider that silently found nothing would make every assertion above vacuous.
        $this->assertGreaterThanOrEqual(10, count(self::entityTableProvider()));
    }

    /**
     * @return list<SplFileInfo>
     */
    private static function entityFiles(): array
    {
        $files = [];
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(self::ENTITY_DIR, FilesystemIterator::SKIP_DOTS),
        );

        /** @var SplFileInfo $file */
        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $files[] = $file;
            }
        }

        return $files;
    }

    private static function classFor(SplFileInfo $file): string
    {
        // Both sides resolved, or the `..` segments in ENTITY_DIR never match the
        // iterator's own path and every class name comes out mangled.
        $root = (string) realpath(self::ENTITY_DIR);
        $relative = substr((string) $file->getRealPath(), strlen($root) + 1);

        return self::NAMESPACE_PREFIX . str_replace(['/', '.php'], ['\\', ''], $relative);
    }

    private function shortName(string $class): string
    {
        return (new ReflectionClass($class))->getShortName();
    }

    private function migrationsMention(string $needle): bool
    {
        foreach (glob(self::MIGRATIONS_DIR . '/*.php') ?: [] as $migration) {
            if (str_contains((string) file_get_contents($migration), $needle)) {
                return true;
            }
        }

        return false;
    }

    /**
     * The installer references a table in both install() and uninstall(), so a check over
     * the whole file would pass with the table only dropped, or only created.
     */
    private function methodBody(string $source, string $method): string
    {
        $start = strpos($source, 'public function ' . $method . '(): void');
        $this->assertIsInt($start, sprintf('Installer::%s() not found.', $method));

        $end = strpos($source, "\n    }", $start);
        $this->assertIsInt($end, sprintf('Could not delimit Installer::%s().', $method));

        return substr($source, $start, $end - $start);
    }
}
