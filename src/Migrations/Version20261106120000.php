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
use Pimcore\Bundle\StudioBackendBundle\Entity\User\UserSettings;

/**
 * Creates the per-user Studio settings table (welcome screen path) for existing installations.
 * Fresh installations get it from the Installer instead.
 *
 * @internal
 */
final class Version20261106120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create the user settings table';
    }

    public function isTransactional(): bool
    {
        return false;
    }

    public function up(Schema $schema): void
    {
        if ($schema->hasTable(UserSettings::TABLE_NAME)) {
            return;
        }

        $table = $schema->createTable(UserSettings::TABLE_NAME);

        $table->addColumn('user', 'integer', ['notnull' => true, 'unsigned' => true]);
        $table->addColumn('welcomeScreenPath', 'string', ['notnull' => false, 'length' => 255]);

        $table->addForeignKeyConstraint(
            'users',
            ['user'],
            ['id'],
            ['onDelete' => 'CASCADE'],
            'fk_' . UserSettings::TABLE_NAME . '_users'
        );

        $table->setPrimaryKey(['user'], 'pk_' . UserSettings::TABLE_NAME);
    }

    public function down(Schema $schema): void
    {
        if (!$schema->hasTable(UserSettings::TABLE_NAME)) {
            return;
        }

        $schema->dropTable(UserSettings::TABLE_NAME);
    }
}
