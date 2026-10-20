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
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\Driver\AttributeDriver;
use Doctrine\Persistence\Mapping\RuntimeReflectionService;
use Pimcore\Bundle\StudioBackendBundle\Entity\Search\SavedSearchConfiguration;
use Pimcore\Bundle\StudioBackendBundle\Installer;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;
use function sprintf;

/**
 * A fresh install builds the saved search table from the installer, while upgrades get later
 * columns from migrations. Every column the entity maps has to be created by the installer too,
 * otherwise reading or writing a saved search fails with "Unknown column" on a fresh install.
 *
 * @internal
 */
final class SavedSearchInstallerSchemaTest extends Unit
{
    public function testTheInstallerCreatesEveryMappedColumn(): void
    {
        $schema = new Schema();
        $installer = new Installer(
            $this->createMock(Connection::class),
            $this->createMock(BundleInterface::class),
        );
        $installer->createSavedSearchConfigurationTable($schema);
        $table = $schema->getTable(SavedSearchConfiguration::TABLE_NAME);

        $metadata = new ClassMetadata(SavedSearchConfiguration::class);
        $metadata->initializeReflection(new RuntimeReflectionService());
        (new AttributeDriver([]))->loadMetadataForClass(SavedSearchConfiguration::class, $metadata);

        foreach ($metadata->fieldMappings as $field) {
            $name = $field->columnName;
            $this->assertTrue(
                $table->hasColumn($name),
                sprintf('The installer does not create the mapped column "%s".', $name),
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
