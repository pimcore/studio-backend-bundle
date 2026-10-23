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
use Doctrine\DBAL\Schema\Table;
use Doctrine\Migrations\AbstractMigration;
use Pimcore\Bundle\StudioBackendBundle\Entity\Grid\GridConfiguration;
use Pimcore\Bundle\StudioBackendBundle\Entity\Search\SavedSearchConfiguration;

/**
 * Keep grid and saved search configurations when their owner is deleted instead of cascading the
 * deletion: the configurations can be shared with other users. The owner foreign key is switched
 * from ON DELETE CASCADE to ON DELETE SET NULL and the owner column is made nullable.
 *
 * @internal
 */
final class Version20260703120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Set grid and saved search configuration owner to NULL on user delete instead of cascading';
    }

    public function up(Schema $schema): void
    {
        $this->switchOwnerOnDelete($schema, GridConfiguration::TABLE_NAME, 'SET NULL');
        $this->switchOwnerOnDelete($schema, SavedSearchConfiguration::TABLE_NAME, 'SET NULL');
    }

    public function down(Schema $schema): void
    {
        $this->switchOwnerOnDelete($schema, GridConfiguration::TABLE_NAME, 'CASCADE');
        $this->switchOwnerOnDelete($schema, SavedSearchConfiguration::TABLE_NAME, 'CASCADE');
    }

    private function switchOwnerOnDelete(Schema $schema, string $tableName, string $onDelete): void
    {
        if (!$schema->hasTable($tableName)) {
            return;
        }

        $table = $schema->getTable($tableName);

        if (!$table->hasColumn('owner')) {
            return;
        }

        // SET NULL requires a nullable column; CASCADE keeps it nullable as well (harmless superset).
        $table->getColumn('owner')->setNotnull(false);

        $foreignKeyName = 'fk_' . $tableName . '_owner_users';
        if ($table->hasForeignKey($foreignKeyName)) {
            $table->removeForeignKey($foreignKeyName);
        }

        $this->addOwnerForeignKey($table, $foreignKeyName, $onDelete);
    }

    private function addOwnerForeignKey(Table $table, string $foreignKeyName, string $onDelete): void
    {
        $table->addForeignKeyConstraint(
            'users',
            ['owner'],
            ['id'],
            ['onDelete' => $onDelete],
            $foreignKeyName
        );
    }
}
