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
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\Driver\AttributeDriver;
use Doctrine\Persistence\Mapping\RuntimeReflectionService;
use Pimcore\Bundle\StudioBackendBundle\Entity\User\UserSettings;
use Pimcore\Bundle\StudioBackendBundle\Installer;
use Pimcore\Bundle\StudioBackendBundle\Migrations\Version20261106120000;
use ReflectionClass;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;
use function sprintf;

/**
 * A fresh install builds the user settings table from the installer, an existing one gets it from
 * the migration. Both have to create every column the entity maps.
 *
 * @internal
 */
final class UserSettingsSchemaTest extends Unit
{
    public function testTheInstallerCreatesEveryMappedColumn(): void
    {
        $schema = new Schema();
        $installer = new Installer(
            $this->createMock(Connection::class),
            $this->createMock(BundleInterface::class),
        );
        $installer->createUserSettingsTable($schema);

        $this->assertCreatesEveryMappedColumn($schema->getTable(UserSettings::TABLE_NAME));
    }

    public function testTheMigrationCreatesEveryMappedColumnAndRunsTwice(): void
    {
        $schema = new Schema();
        $migration = $this->createMigration();
        $migration->up($schema);
        $migration->up($schema);

        $this->assertCreatesEveryMappedColumn($schema->getTable(UserSettings::TABLE_NAME));
    }

    public function testTheMigrationDropsTheTableAndRunsTwice(): void
    {
        $schema = new Schema();
        $migration = $this->createMigration();
        $migration->up($schema);
        $migration->down($schema);
        $migration->down($schema);

        $this->assertFalse($schema->hasTable(UserSettings::TABLE_NAME));
    }

    private function createMigration(): Version20261106120000
    {
        return (new ReflectionClass(Version20261106120000::class))->newInstanceWithoutConstructor();
    }

    private function assertCreatesEveryMappedColumn(Table $table): void
    {
        $metadata = new ClassMetadata(UserSettings::class);
        $metadata->initializeReflection(new RuntimeReflectionService());
        (new AttributeDriver([]))->loadMetadataForClass(UserSettings::class, $metadata);

        foreach ($metadata->fieldMappings as $field) {
            $name = $field->columnName;
            $this->assertTrue(
                $table->hasColumn($name),
                sprintf('The mapped column "%s" is not created.', $name),
            );

            $column = $table->getColumn($name);
            $this->assertSame(
                $field->type,
                Type::getTypeRegistry()->lookupName($column->getType()),
                sprintf('Column "%s" has a different type than the entity maps.', $name),
            );
            $this->assertSame(
                !($field->nullable ?? false),
                $column->getNotnull(),
                sprintf('Column "%s" has a different nullability than the entity maps.', $name),
            );

            if ($field->length !== null) {
                $this->assertSame(
                    $field->length,
                    $column->getLength(),
                    sprintf('Column "%s" has a different length than the entity maps.', $name),
                );
            }
        }
    }
}
