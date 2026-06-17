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

namespace Pimcore\Bundle\StudioBackendBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Pimcore\Bundle\StudioBackendBundle\Entity\Search\SavedSearchConfiguration;
use Pimcore\Bundle\StudioBackendBundle\Entity\Search\SavedSearchConfigurationShare;

/**
 * @internal
 */
final class Version20260617101829 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create saved search configuration and share tables';
    }

    public function up(Schema $schema): void
    {
        $this->createSavedSearchConfigurationTable($schema);
        $this->createSavedSearchConfigurationSharesTable($schema);
    }

    public function down(Schema $schema): void
    {
        if ($schema->hasTable(SavedSearchConfigurationShare::TABLE_NAME)) {
            $schema->dropTable(SavedSearchConfigurationShare::TABLE_NAME);
        }

        if ($schema->hasTable(SavedSearchConfiguration::TABLE_NAME)) {
            $schema->dropTable(SavedSearchConfiguration::TABLE_NAME);
        }
    }

    private function createSavedSearchConfigurationTable(Schema $schema): void
    {
        if ($schema->hasTable(SavedSearchConfiguration::TABLE_NAME)) {
            return;
        }

        $table = $schema->createTable(SavedSearchConfiguration::TABLE_NAME);

        $table->addColumn('id', 'integer', [
            'autoincrement' => true,
            'unsigned' => true,
        ]);

        $table->addColumn('owner', 'integer', ['notnull' => false, 'unsigned' => true]);
        $table->addColumn('name', 'string', ['notnull' => true, 'length' => 255]);
        $table->addColumn('description', 'text', ['notnull' => false]);
        $table->addColumn('classId', 'string', ['notnull' => false, 'length' => 50]);
        $table->addColumn('shareGlobal', 'boolean', ['notnull' => true]);
        $table->addColumn('createMenuShortcut', 'boolean', ['notnull' => true]);
        $table->addColumn('columns', 'json', ['notnull' => true]);
        $table->addColumn('filter', 'json', ['notnull' => false]);
        $table->addColumn('creationDate', 'datetime', ['notnull' => true]);
        $table->addColumn('modificationDate', 'datetime', ['notnull' => true]);

        $table->setPrimaryKey(['id'], 'pk_'.SavedSearchConfiguration::TABLE_NAME);

        $table->addForeignKeyConstraint(
            'users',
            ['owner'],
            ['id'],
            ['onDelete' => 'CASCADE'],
            'fk_'.SavedSearchConfiguration::TABLE_NAME.'_owner_users'
        );
    }

    private function createSavedSearchConfigurationSharesTable(Schema $schema): void
    {
        if ($schema->hasTable(SavedSearchConfigurationShare::TABLE_NAME)) {
            return;
        }

        $table = $schema->createTable(SavedSearchConfigurationShare::TABLE_NAME);

        $table->addColumn('user', 'integer', ['notnull' => false, 'unsigned' => true]);
        $table->addColumn('configuration', 'integer', ['notnull' => false, 'unsigned' => true]);

        $table->addForeignKeyConstraint(
            'users',
            ['user'],
            ['id'],
            ['onDelete' => 'CASCADE'],
            'fk_'.SavedSearchConfigurationShare::TABLE_NAME.'_users'
        );

        $table->addForeignKeyConstraint(
            SavedSearchConfiguration::TABLE_NAME,
            ['configuration'],
            ['id'],
            ['onDelete' => 'CASCADE'],
            'fk_'.SavedSearchConfigurationShare::TABLE_NAME.'_configurations'
        );

        $table->setPrimaryKey(['user', 'configuration'], 'pk_'.SavedSearchConfigurationShare::TABLE_NAME);
    }
}
